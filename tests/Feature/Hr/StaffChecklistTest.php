<?php

namespace Tests\Feature\Hr;

use App\Models\User;
use App\Modules\Hr\Models\Branch;
use App\Modules\Hr\Models\ChecklistTemplate;
use App\Modules\Hr\Models\DepartureReason;
use App\Modules\Hr\Models\StaffChecklist;
use App\Modules\Hr\Models\StaffChecklistTask;
use App\Modules\Hr\Models\StaffOnboardingRequest;
use App\Modules\Rbac\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class StaffChecklistTest extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    protected Branch $branchA;

    protected User $approverA;

    protected User $managerA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();

        Permission::create(['name' => 'HRMS-StaffManagement', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'HRMS-Approval-L2', 'guard_name' => 'sanctum']);

        $this->inTenantA(function () {
            $this->branchA = Branch::factory()->for($this->tenantA)->create();

            // userA = HR (no L2), approverA = L2, managerA = plain staff.
            $this->userA->givePermissionTo('HRMS-StaffManagement');
            $this->approverA = User::factory()->for($this->tenantA)->create(['status' => 'active']);
            $this->approverA->givePermissionTo(['HRMS-StaffManagement', 'HRMS-Approval-L2']);
            $this->managerA = User::factory()->for($this->tenantA)->create(['status' => 'active']);
        });
    }

    protected function inTenantA(\Closure $callback): mixed
    {
        return $this->tenantContext()->runAs($this->tenantA->id, $callback);
    }

    /** HR submits a hire reporting to managerA; returns [staffId, onboardingRequestId]. */
    protected function submitHire(array $overrides = []): array
    {
        $staffId = $this->actingAs($this->userA, 'sanctum')->postJson('/api/staff/create', array_merge([
            'name' => 'New Hire',
            'email' => 'new-hire@example.test',
            'phone' => '555-0100',
            'designation' => 'Engineer',
            'joined_at' => now()->addDays(10)->toDateString(),
            'is_remote' => false,
            'dob' => '1990-01-01',
            'branches' => [$this->branchA->id],
            'hierarchy_parent_id' => $this->managerA->id,
        ], $overrides))->assertCreated()->json('data.id');

        $requestId = $this->inTenantA(fn () => StaffOnboardingRequest::where('user_id', $staffId)->value('id'));

        return [$staffId, $requestId];
    }

    protected function approveHire(int $requestId): void
    {
        $this->actingAs($this->approverA, 'sanctum')->postJson('/api/staff/onboard/approve', ['id' => $requestId])->assertOk();
    }

    protected function checklistFor(int $staffId, string $type = 'onboarding'): ?StaffChecklist
    {
        return $this->inTenantA(fn () => StaffChecklist::with('tasks')->where('user_id', $staffId)->where('type', $type)->latest('id')->first());
    }

    public function test_approving_a_hire_starts_the_default_checklist_with_tasks_assigned_and_dated(): void
    {
        [$staffId, $requestId] = $this->submitHire();

        $this->assertNull($this->checklistFor($staffId), 'No checklist before approval.');

        $this->approveHire($requestId);

        $checklist = $this->checklistFor($staffId);
        $this->assertNotNull($checklist);
        $this->assertSame('in_progress', $checklist->status);

        $equipment = $checklist->tasks->firstWhere('title', 'Prepare equipment and system accounts');
        $this->assertSame($this->managerA->id, $equipment->assignee_user_id, 'Manager task goes to the hire\'s manager.');
        $this->assertSame(now()->addDays(7)->toDateString(), $equipment->due_date->toDateString(), 'Due 3 days before the start date.');

        $details = $checklist->tasks->firstWhere('title', 'Complete personal and bank details');
        $this->assertSame($staffId, $details->assignee_user_id, 'Employee task goes to the hire.');
    }

    public function test_a_rejected_hire_never_gets_a_checklist(): void
    {
        [$staffId, $requestId] = $this->submitHire();

        $this->actingAs($this->approverA, 'sanctum')
            ->postJson('/api/staff/onboard/reject', ['id' => $requestId, 'reason' => 'No'])
            ->assertOk();

        $this->assertNull($this->checklistFor($staffId));
    }

    public function test_the_assignee_sees_and_completes_their_task_but_an_unrelated_colleague_cannot(): void
    {
        [$staffId, $requestId] = $this->submitHire();
        $this->approveHire($requestId);

        $myTasks = $this->actingAs($this->managerA, 'sanctum')->getJson('/api/checklists/my-tasks')->assertOk()->json('data');
        $this->assertCount(3, $myTasks, 'The starter onboarding template has 3 manager tasks.');

        $taskId = $myTasks[0]['id'];
        $colleague = $this->inTenantA(fn () => User::factory()->for($this->tenantA)->create(['status' => 'active']));

        $this->actingAs($colleague, 'sanctum')
            ->postJson('/api/checklists/task/update', ['id' => $taskId, 'status' => 'done'])
            ->assertForbidden();

        $this->actingAs($this->managerA, 'sanctum')
            ->postJson('/api/checklists/task/update', ['id' => $taskId, 'status' => 'done', 'note' => 'Laptop ready'])
            ->assertOk();

        $this->inTenantA(function () use ($taskId) {
            $task = StaffChecklistTask::findOrFail($taskId);
            $this->assertSame('done', $task->status);
            $this->assertSame($this->managerA->id, $task->completed_by);
        });
    }

    public function test_a_role_assigned_task_can_be_done_by_anyone_holding_the_role(): void
    {
        $itPerson = $this->inTenantA(function () {
            $role = Role::create(['name' => 'IT Support', 'guard_name' => 'sanctum']);
            $person = User::factory()->for($this->tenantA)->create(['status' => 'active']);
            $person->assignRole($role);

            return $person;
        });
        $roleId = $this->inTenantA(fn () => Role::where('name', 'IT Support')->value('id'));

        $templateId = $this->actingAs($this->userA, 'sanctum')->postJson('/api/checklist-templates/create', [
            'type' => 'onboarding',
            'name' => 'Engineering onboarding',
            'tasks' => [['title' => 'Create GitHub account', 'assignee_type' => 'role', 'assignee_role_id' => $roleId]],
        ])->assertCreated()->json('data.id');

        [$staffId, $requestId] = $this->submitHire(['checklist_template_id' => $templateId]);
        $this->approveHire($requestId);

        $myTasks = $this->actingAs($itPerson, 'sanctum')->getJson('/api/checklists/my-tasks')->assertOk()->json('data');
        $this->assertCount(1, $myTasks);
        $this->assertSame('Create GitHub account', $myTasks[0]['title']);

        $this->actingAs($itPerson, 'sanctum')
            ->postJson('/api/checklists/task/update', ['id' => $myTasks[0]['id'], 'status' => 'done'])
            ->assertOk();

        $this->assertSame('completed', $this->checklistFor($staffId)->status, 'Its only task is done, so the checklist completes.');
    }

    public function test_required_tasks_cannot_be_skipped_and_status_follows_the_tasks(): void
    {
        [$staffId, $requestId] = $this->submitHire();
        $this->approveHire($requestId);

        $tasks = $this->checklistFor($staffId)->tasks;
        $required = $tasks->firstWhere('is_required', true);
        $optional = $tasks->firstWhere('is_required', false);

        $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/checklists/task/update', ['id' => $required->id, 'status' => 'skipped'])
            ->assertUnprocessable();

        foreach ($tasks as $task) {
            $status = $task->is_required ? 'done' : 'skipped';
            $this->actingAs($this->userA, 'sanctum')
                ->postJson('/api/checklists/task/update', ['id' => $task->id, 'status' => $status])
                ->assertOk();
        }

        $this->assertSame('completed', $this->checklistFor($staffId)->status);

        $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/checklists/task/update', ['id' => $optional->id, 'status' => 'todo'])
            ->assertOk();

        $this->assertSame('in_progress', $this->checklistFor($staffId)->status, 'Reopening a task reopens the checklist.');
    }

    public function test_cancelling_a_scheduled_exit_cancels_its_checklist(): void
    {
        $leaver = $this->inTenantA(fn () => User::factory()->for($this->tenantA)->create(['status' => 'active']));
        $reasonId = $this->inTenantA(fn () => DepartureReason::where('name', 'Resignation')->value('id'));

        $this->actingAs($this->approverA, 'sanctum')->postJson('/api/staff/offboard', [
            'id' => $leaver->id,
            'last_working_day' => now()->addWeeks(2)->toDateString(),
            'departure_reason_id' => $reasonId,
        ])->assertCreated();

        $this->assertSame('in_progress', $this->checklistFor($leaver->id, 'offboarding')->status);

        $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/staff/cancel-offboarding', ['id' => $leaver->id])
            ->assertOk();

        $this->assertSame('cancelled', $this->checklistFor($leaver->id, 'offboarding')->status);
    }

    public function test_approval_still_works_when_there_is_no_default_template(): void
    {
        $this->inTenantA(fn () => ChecklistTemplate::query()->update(['is_default' => false]));

        [$staffId, $requestId] = $this->submitHire(['joined_at' => now()->toDateString()]);
        $this->approveHire($requestId);

        $this->inTenantA(fn () => $this->assertSame('active', User::findOrFail($staffId)->status));
        $this->assertNull($this->checklistFor($staffId));
    }

    public function test_the_new_hire_can_view_their_own_checklist_but_a_colleague_cannot(): void
    {
        [$staffId, $requestId] = $this->submitHire();
        $this->approveHire($requestId);
        $checklistId = $this->checklistFor($staffId)->id;

        $hire = $this->inTenantA(fn () => User::findOrFail($staffId));
        $colleague = $this->inTenantA(fn () => User::factory()->for($this->tenantA)->create(['status' => 'active']));

        $this->actingAs($hire, 'sanctum')->postJson('/api/checklists/show', ['id' => $checklistId])
            ->assertOk()
            ->assertJsonCount(5, 'data.tasks');

        $this->actingAs($colleague, 'sanctum')->postJson('/api/checklists/show', ['id' => $checklistId])->assertForbidden();
    }

    public function test_making_a_template_the_default_replaces_the_previous_default(): void
    {
        $newId = $this->actingAs($this->userA, 'sanctum')->postJson('/api/checklist-templates/create', [
            'type' => 'onboarding',
            'name' => 'Sales onboarding',
            'is_default' => true,
            'tasks' => [['title' => 'CRM training', 'assignee_type' => 'employee']],
        ])->assertCreated()->json('data.id');

        $this->inTenantA(function () use ($newId) {
            $this->assertSame($newId, ChecklistTemplate::defaultFor('onboarding')->id);
            $this->assertSame(1, ChecklistTemplate::where('type', 'onboarding')->where('is_default', true)->count());
        });
    }

    public function test_templates_and_tasks_cannot_reach_into_another_tenant(): void
    {
        $roleB = $this->tenantContext()->runAs($this->tenantB->id, fn () => Role::create(['name' => 'B Role', 'guard_name' => 'sanctum']));

        $this->actingAs($this->userA, 'sanctum')->postJson('/api/checklist-templates/create', [
            'type' => 'onboarding',
            'name' => 'Sneaky',
            'tasks' => [['title' => 'x', 'assignee_type' => 'role', 'assignee_role_id' => $roleB->id]],
        ])->assertUnprocessable()->assertJsonValidationErrors('tasks.0.assignee_role_id');

        [$staffId, $requestId] = $this->submitHire();
        $this->approveHire($requestId);
        $taskId = $this->checklistFor($staffId)->tasks->first()->id;

        $this->tenantContext()->runAs($this->tenantB->id, fn () => $this->userB->givePermissionTo('HRMS-StaffManagement'));

        $this->actingAs($this->userB, 'sanctum')
            ->postJson('/api/checklists/task/update', ['id' => $taskId, 'status' => 'done'])
            ->assertUnprocessable();
    }
}
