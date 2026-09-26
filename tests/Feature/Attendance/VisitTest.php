<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Modules\Attendance\Models\Visit;
use App\Modules\Attendance\Models\VisitLocation;
use App\Modules\Attendance\Models\VisitPlan;
use Carbon\CarbonImmutable;
use Illuminate\Testing\TestResponse;

/**
 * A home-care company: Ama visits clients at home. Mrs Patel lives at
 * 51.5007, -0.1246 (150 m radius). 2 March 2026 is a Monday; company UTC.
 */
class VisitTest extends AttendanceTestCase
{
    private User $coordinator;

    private User $carer;

    private VisitLocation $patel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inTenantA(function () {
            $this->coordinator = $this->makeStaff(['name' => 'Cora Coordinator']);
            $this->coordinator->givePermissionTo('HRMS-VisitPlanning');
            $this->carer = $this->makeStaff(['name' => 'Ama Carer', 'attendance_mode' => 'visits']);
            $this->patel = VisitLocation::create(['name' => 'Mrs A. Patel', 'address' => '1 Parliament Sq', 'latitude' => 51.5007, 'longitude' => -0.1246, 'geofence_radius_m' => 150]);
        });
    }

    private function visitAt(string $start, string $end, ?VisitLocation $where = null, ?User $who = null): Visit
    {
        return $this->inTenantA(fn () => Visit::create([
            'visit_location_id' => ($where ?? $this->patel)->id, 'user_id' => ($who ?? $this->carer)->id,
            'date' => substr($start, 0, 10), 'planned_start' => CarbonImmutable::parse($start, 'UTC'), 'planned_end' => CarbonImmutable::parse($end, 'UTC'),
        ]));
    }

    private function checkIn(Visit $visit, string $at, array $where = ['latitude' => 51.5008, 'longitude' => -0.1245]): TestResponse
    {
        $this->travelTo(CarbonImmutable::parse($at, 'UTC'));

        return $this->actingAs($this->carer, 'sanctum')->postJson('/api/visits/check-in', ['visit_id' => $visit->id] + $where);
    }

    private function checkOut(Visit $visit, string $at, array $where = ['latitude' => 51.5008, 'longitude' => -0.1245]): TestResponse
    {
        $this->travelTo(CarbonImmutable::parse($at, 'UTC'));

        return $this->actingAs($this->carer, 'sanctum')->postJson('/api/visits/check-out', ['visit_id' => $visit->id, 'note' => 'All well'] + $where);
    }

    private function visit(int $id): Visit
    {
        return $this->inTenantA(fn () => Visit::findOrFail($id));
    }

    public function test_a_plan_creates_two_weeks_of_visits_on_its_weekdays_once(): void
    {
        $this->travelTo('2026-03-02 07:00');

        $this->actingAs($this->coordinator, 'sanctum')->postJson('/api/visits/plans/create', [
            'visit_location_id' => $this->patel->id, 'user_id' => $this->carer->id,
            'weekdays' => [1, 3, 5], 'start_time' => '08:00', 'end_time' => '09:00', 'starts_on' => '2026-03-02',
        ])->assertCreated();

        // Mon 2 → Mon 16 March: 2, 4, 6, 9, 11, 13, 16.
        $dates = $this->inTenantA(fn () => Visit::orderBy('date')->pluck('date')->map->toDateString()->all());
        $this->assertSame(['2026-03-02', '2026-03-04', '2026-03-06', '2026-03-09', '2026-03-11', '2026-03-13', '2026-03-16'], $dates);

        $this->artisan('visits:generate')->assertSuccessful();
        $this->assertSame(7, $this->inTenantA(fn () => Visit::count()), 'Generating again adds nothing.');

        $this->actingAs($this->carer, 'sanctum')->getJson('/api/visits/my-day')
            ->assertOk()->assertJsonCount(1, 'data.visits')->assertJsonPath('data.visits.0.location.name', 'Mrs A. Patel')
            ->assertJsonPath('data.visits.0.planned_start', '2026-03-02T08:00:00+00:00');
    }

    public function test_checking_in_and_out_at_the_address_is_verified(): void
    {
        $visit = $this->visitAt('2026-03-02 08:00', '2026-03-02 09:00');

        $this->checkIn($visit, '2026-03-02 08:02')->assertOk()->assertJsonPath('data.status', 'in_progress')->assertJsonPath('data.check_in_within_range', true)->assertJsonPath('message', 'Checked in.');
        $this->checkOut($visit, '2026-03-02 09:05')->assertOk()->assertJsonPath('data.status', 'completed')->assertJsonPath('data.worked_minutes', 63)
            ->assertJsonPath('data.countable_minutes', 63)->assertJsonPath('data.review_status', null)->assertJsonPath('data.caregiver_note', 'All well');
    }

    public function test_away_from_the_address_is_recorded_and_flagged_not_refused(): void
    {
        $visit = $this->visitAt('2026-03-02 08:00', '2026-03-02 09:00');

        // About 1.1 km away.
        $this->checkIn($visit, '2026-03-02 08:00', ['latitude' => 51.5107, 'longitude' => -0.1246])
            ->assertOk()->assertJsonPath('data.check_in_within_range', false)->assertJsonPath('data.review_status', 'needs_review')
            ->assertJsonPath('message', 'Checked in. You’re 1112 m from the client’s address, so a coordinator will check this visit.');
        // Checking out at the address doesn't clear the check-in's flag.
        $this->checkOut($visit, '2026-03-02 09:00')->assertJsonPath('message', 'Checked out.')->assertJsonPath('data.review_status', 'needs_review');
        $this->checkOut($visit, '2026-03-02 09:10')->assertStatus(409);

        $this->assertSame(0, $this->visit($visit->id)->countableMinutes(), 'Not counted until a coordinator accepts it.');

        // No location sent at all: also flagged.
        $other = $this->visitAt('2026-03-02 10:00', '2026-03-02 11:00');
        $this->checkIn($other, '2026-03-02 10:00', [])->assertOk()->assertJsonPath('data.review_status', 'needs_review');
    }

    public function test_only_your_own_visit_one_at_a_time_and_only_when_due(): void
    {
        $first = $this->visitAt('2026-03-02 08:00', '2026-03-02 09:00');
        $second = $this->visitAt('2026-03-02 10:00', '2026-03-02 11:00');
        $someoneElses = $this->visitAt('2026-03-02 08:00', '2026-03-02 09:00', who: $this->employee);
        $nextWeek = $this->visitAt('2026-03-09 08:00', '2026-03-09 09:00');

        $this->checkIn($someoneElses, '2026-03-02 08:00')->assertUnprocessable();
        $this->checkIn($nextWeek, '2026-03-02 08:00')->assertStatus(409);
        $this->checkIn($first, '2026-03-02 08:00')->assertOk();
        $this->checkIn($second, '2026-03-02 08:30')->assertStatus(409)->assertJsonPath('message', 'Check out of your visit to Mrs A. Patel first.');
        $this->checkOut($second, '2026-03-02 08:30')->assertStatus(409);
    }

    public function test_the_day_is_built_from_visits_and_only_verified_time_counts(): void
    {
        $done = $this->visitAt('2026-03-02 08:00', '2026-03-02 09:00');
        $flagged = $this->visitAt('2026-03-02 10:00', '2026-03-02 11:00');
        $missed = $this->visitAt('2026-03-02 14:00', '2026-03-02 15:00');

        $this->checkIn($done, '2026-03-02 08:00');
        $this->checkOut($done, '2026-03-02 09:00');
        $this->checkIn($flagged, '2026-03-02 10:20', ['latitude' => 51.52, 'longitude' => -0.1246]); // late and far
        $this->checkOut($flagged, '2026-03-02 11:00');

        $this->closeOut('2026-03-03 01:00');

        $this->assertSame('missed', $this->visit($missed->id)->status);
        $day = $this->day('2026-03-02', $this->carer);
        $this->assertSame('present', $day->status);
        $this->assertSame('visits', $day->expected);
        $this->assertSame(180, $day->scheduled_minutes);
        $this->assertSame(60, $day->worked_minutes, 'The flagged visit waits for review.');
        $this->assertTrue($day->is_late);
        $this->assertNotNull($day->closed_at);

        // Accepting it counts its 40 minutes and re-settles the day.
        $this->actingAs($this->coordinator, 'sanctum')->getJson('/api/visits/review-queue')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->coordinator, 'sanctum')->postJson('/api/visits/review', ['id' => $flagged->id, 'decision' => 'accept'])->assertOk();
        $this->assertSame(100, $this->day('2026-03-02', $this->carer)->worked_minutes);
    }

    public function test_all_visits_missed_is_absent_and_no_visits_is_a_day_off(): void
    {
        $this->visitAt('2026-03-02 08:00', '2026-03-02 09:00');

        $this->closeOut('2026-03-04 01:00');

        $this->assertSame('absent', $this->day('2026-03-02', $this->carer)->status);
        $this->assertSame('day_off', $this->day('2026-03-03', $this->carer)->status);
    }

    public function test_a_visit_never_checked_out_needs_its_times_set_before_it_counts(): void
    {
        $visit = $this->visitAt('2026-03-02 08:00', '2026-03-02 09:00');
        $this->checkIn($visit, '2026-03-02 08:00');

        $this->closeOut('2026-03-03 01:00');
        $v = $this->visit($visit->id);
        $this->assertSame('completed', $v->status);
        $this->assertTrue($v->missed_checkout);
        $this->assertSame('needs_review', $v->review_status);

        $this->actingAs($this->coordinator, 'sanctum')->postJson('/api/visits/review', ['id' => $visit->id, 'decision' => 'accept'])->assertStatus(409);
        $this->actingAs($this->coordinator, 'sanctum')->postJson('/api/visits/set-times', ['id' => $visit->id, 'in' => '08:00', 'out' => '08:50', 'note' => 'Confirmed with the family'])
            ->assertOk()->assertJsonPath('data.review_status', 'accepted')->assertJsonPath('data.countable_minutes', 50);

        $this->assertSame(50, $this->day('2026-03-02', $this->carer)->worked_minutes);
    }

    public function test_a_rejected_visit_never_counts(): void
    {
        $visit = $this->visitAt('2026-03-02 08:00', '2026-03-02 09:00');
        $this->checkIn($visit, '2026-03-02 08:00', ['latitude' => 51.53, 'longitude' => -0.1246]);
        $this->checkOut($visit, '2026-03-02 09:00');

        $this->actingAs($this->coordinator, 'sanctum')->postJson('/api/visits/review', ['id' => $visit->id, 'decision' => 'reject'])->assertUnprocessable();
        $this->actingAs($this->coordinator, 'sanctum')->postJson('/api/visits/review', ['id' => $visit->id, 'decision' => 'reject', 'note' => 'Was at home'])->assertOk();

        $this->closeOut('2026-03-03 01:00');
        $this->assertSame(0, $this->day('2026-03-02', $this->carer)->worked_minutes);
    }

    public function test_coordinators_reassign_move_and_cancel_single_visits(): void
    {
        $this->travelTo('2026-03-01 12:00');
        $visit = $this->visitAt('2026-03-02 08:00', '2026-03-02 09:00');
        $cover = $this->inTenantA(fn () => $this->makeStaff(['name' => 'Ben Cover', 'attendance_mode' => 'visits']));

        $this->actingAs($this->coordinator, 'sanctum')->postJson('/api/visits/update', ['id' => $visit->id, 'user_id' => $cover->id, 'start_time' => '09:30', 'end_time' => '10:15'])
            ->assertOk()->assertJsonPath('data.staff.name', 'Ben Cover')->assertJsonPath('data.planned_start', '2026-03-02T09:30:00+00:00')->assertJsonPath('data.planned_minutes', 45);

        $this->actingAs($this->coordinator, 'sanctum')->postJson('/api/visits/cancel', ['id' => $visit->id, 'reason' => 'In hospital'])->assertOk()->assertJsonPath('data.status', 'cancelled');

        $board = $this->actingAs($this->coordinator, 'sanctum')->getJson('/api/visits/day?date=2026-03-02')->assertOk();
        $this->assertSame(0, $board->json('data.summary.planned'));
        $this->assertContains('Ama Carer', collect($board->json('data.caregivers'))->pluck('name'));
    }

    public function test_changing_a_plan_replaces_its_future_visits_but_not_hand_changed_ones(): void
    {
        $this->travelTo('2026-03-02 07:00');
        $planId = $this->actingAs($this->coordinator, 'sanctum')->postJson('/api/visits/plans/create', [
            'visit_location_id' => $this->patel->id, 'user_id' => $this->carer->id, 'weekdays' => [1, 3], 'start_time' => '08:00', 'end_time' => '09:00', 'starts_on' => '2026-03-02',
        ])->json('data.id');

        $handMoved = $this->inTenantA(fn () => Visit::whereDate('date', '2026-03-04')->first());
        $this->actingAs($this->coordinator, 'sanctum')->postJson('/api/visits/update', ['id' => $handMoved->id, 'start_time' => '11:00', 'end_time' => '12:00'])->assertOk();

        $this->actingAs($this->coordinator, 'sanctum')->postJson('/api/visits/plans/update', [
            'id' => $planId, 'visit_location_id' => $this->patel->id, 'user_id' => $this->carer->id, 'weekdays' => [1, 3], 'start_time' => '07:30', 'end_time' => '08:30', 'starts_on' => '2026-03-02',
        ])->assertOk();

        $this->assertSame('11:00', $this->visit($handMoved->id)->planned_start->format('H:i'));
        $this->assertSame('07:30', $this->inTenantA(fn () => Visit::whereDate('date', '2026-03-09')->first())->planned_start->format('H:i'));

        // Ending the plan (the client's care has ended) removes every visit still to come, hand-moved or not.
        $this->actingAs($this->coordinator, 'sanctum')->postJson('/api/visits/plans/delete', ['id' => $planId])->assertOk();
        $this->assertSame(0, $this->inTenantA(fn () => Visit::where('visit_plan_id', $planId)->count()));
        $this->assertNotNull($this->inTenantA(fn () => VisitPlan::find($planId))->ends_on);
    }

    public function test_coordinator_screens_need_the_visit_permission(): void
    {
        $this->actingAs($this->carer, 'sanctum')->getJson('/api/visits/day')->assertForbidden();
        $this->actingAs($this->carer, 'sanctum')->getJson('/api/visits/locations/list')->assertForbidden();
        $this->actingAs($this->carer, 'sanctum')->getJson('/api/visits/review-queue')->assertForbidden();
        $this->actingAs($this->hr, 'sanctum')->postJson('/api/visits/plans/create', [])->assertForbidden();

        $this->actingAs($this->coordinator, 'sanctum')->postJson('/api/visits/create', [
            'visit_location_id' => $this->patel->id, 'user_id' => $this->userB->id, 'date' => '2026-03-02', 'start_time' => '08:00', 'end_time' => '09:00',
        ])->assertUnprocessable();
    }
}
