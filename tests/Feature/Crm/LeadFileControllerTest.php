<?php

namespace Tests\Feature\Crm;

use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Models\LeadFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\Support\SeedsTwoTenants;
use Tests\TestCase;

class LeadFileControllerTest extends TestCase
{
    use RefreshDatabase, SeedsTwoTenants;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTwoTenants();

        Permission::findOrCreate('CRM-ManageLeads', 'sanctum');
        Storage::fake('local');
    }

    protected function givePermission(): void
    {
        $this->tenantContext()->runAs($this->tenantA->id, fn () => $this->userA->givePermissionTo('CRM-ManageLeads'));
    }

    public function test_a_user_without_the_permission_cannot_upload(): void
    {
        $lead = $this->asTenantOf($this->userA, fn () => Lead::factory()->for($this->tenantA)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-files/create', [
            'lead_id' => $lead->id, 'file' => UploadedFile::fake()->create('quote.pdf', 100, 'application/pdf'),
        ]);

        $response->assertForbidden();
    }

    public function test_it_uploads_a_file_and_stores_it_privately(): void
    {
        $this->givePermission();

        $lead = $this->asTenantOf($this->userA, fn () => Lead::factory()->for($this->tenantA)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-files/create', [
            'lead_id' => $lead->id, 'file' => UploadedFile::fake()->create('quote.pdf', 100, 'application/pdf'),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.original_name', 'quote.pdf');

        $path = $response->json('data.path');
        Storage::disk('local')->assertExists($path);
        // Never the uploaded filename itself — see LeadFileController's docblock.
        $this->assertNotSame('quote.pdf', basename($path));
    }

    public function test_it_rejects_a_disallowed_file_type(): void
    {
        $this->givePermission();

        $lead = $this->asTenantOf($this->userA, fn () => Lead::factory()->for($this->tenantA)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-files/create', [
            'lead_id' => $lead->id, 'file' => UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload'),
        ]);

        $response->assertUnprocessable();
    }

    public function test_it_rejects_a_file_over_the_size_limit(): void
    {
        $this->givePermission();

        $lead = $this->asTenantOf($this->userA, fn () => Lead::factory()->for($this->tenantA)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-files/create', [
            'lead_id' => $lead->id, 'file' => UploadedFile::fake()->create('big.pdf', 20000, 'application/pdf'),
        ]);

        $response->assertUnprocessable();
    }

    public function test_it_downloads_a_file(): void
    {
        $this->givePermission();

        $path = UploadedFile::fake()->create('quote.pdf', 50, 'application/pdf')->store('leads/test', 'local');
        $file = $this->asTenantOf($this->userA, fn () => LeadFile::factory()->for($this->tenantA)->create(['path' => $path, 'original_name' => 'quote.pdf']));

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-files/download', ['id' => $file->id]);

        $response->assertOk();
    }

    public function test_deleting_a_file_removes_it_from_disk(): void
    {
        $this->givePermission();

        $path = UploadedFile::fake()->create('quote.pdf', 50, 'application/pdf')->store('leads/test', 'local');
        $file = $this->asTenantOf($this->userA, fn () => LeadFile::factory()->for($this->tenantA)->create(['path' => $path]));

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-files/delete', ['id' => $file->id]);

        $response->assertOk();
        Storage::disk('local')->assertMissing($path);
        $this->asTenantOf($this->userA, fn () => $this->assertNull(LeadFile::find($file->id)));
    }

    public function test_it_cannot_download_a_file_belonging_to_another_tenant(): void
    {
        $fileB = $this->asTenantOf($this->userB, fn () => LeadFile::factory()->for($this->tenantB)->create());

        $response = $this->actingAs($this->userA, 'sanctum')->postJson('/api/lead-files/download', ['id' => $fileB->id]);

        $response->assertUnprocessable();
    }
}
