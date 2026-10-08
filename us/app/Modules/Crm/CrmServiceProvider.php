<?php

namespace App\Modules\Crm;

use App\Models\User;
use App\Modules\Crm\Models\LeadPipeline;
use App\Modules\Crm\Models\LeadSource;
use App\Modules\Crm\Models\LeadType;
use App\Modules\Platform\Models\Tenant;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CrmServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('manage-leads', function (User $user) {
            return $user->can('CRM-ManageLeads');
        });

        // Every new tenant starts with a default pipeline + stages and a
        // starter source/type list — same "new tenant gets sensible
        // defaults" pattern as HrServiceProvider (departure reasons,
        // checklist templates).
        Tenant::created(function (Tenant $tenant) {
            LeadSource::seedDefaultsFor($tenant);
            LeadType::seedDefaultsFor($tenant);
            LeadPipeline::seedDefaultsFor($tenant);
        });
    }
}
