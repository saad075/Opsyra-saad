<?php

namespace App\Modules\Setup;

use App\Models\User;
use App\Modules\Setup\Contracts\SetupStepProvider;
use App\Modules\Setup\Steps\CompanySetupSteps;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * The Setup wizard (docs/research/setup-wizard.md). Other modules add their
 * own steps by tagging a SetupStepProvider in their service provider.
 */
class SetupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(CompanySetupSteps::class, SetupStepProvider::TAG);
    }

    public function boot(): void
    {
        // New permission — SLMS360 had none guarding its organisation
        // settings. Whoever holds it sees the wizard and edits the company
        // profile; each step's own screens keep their own permissions.
        Gate::define('run-setup', fn (User $user) => $user->can('HRMS-CompanySettings'));
    }
}
