<?php

namespace Tests\Feature\Platform;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The pre-deployment safety check. The test database runs as the same
 * kind of user production must (not a superuser, no BYPASSRLS), in UTC,
 * with every company table protected — so it passes; an unprotected
 * company table makes it fail.
 */
class PreflightTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_correctly_set_up_database_passes(): void
    {
        $this->artisan('opsyra:preflight --tables')
            ->expectsOutputToContain('is not a superuser')
            ->expectsOutputToContain('Every company table has row-level security on and forced')
            ->expectsOutputToContain('Preflight passed.')
            ->assertSuccessful();
    }

    public function test_a_company_table_without_row_level_security_stops_the_deploy(): void
    {
        DB::statement('create table preflight_probe (id bigserial primary key, tenant_id bigint not null)');

        $this->artisan('opsyra:preflight --tables')
            ->expectsOutputToContain('Not protected: preflight_probe')
            ->assertFailed();
    }

    public function test_debug_output_in_production_stops_the_deploy(): void
    {
        $this->app['env'] = 'production';
        config(['app.debug' => true]);

        $this->artisan('opsyra:preflight')->expectsOutputToContain('Debug output is off in production')->assertFailed();
    }
}
