<?php

namespace App\Modules\Platform;

use App\Modules\Platform\Console\CreateTenantCommand;
use App\Modules\Platform\Console\PreflightCommand;
use App\Modules\Platform\Console\ResetDemoCommand;
use Illuminate\Support\ServiceProvider;

class PlatformServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([CreateTenantCommand::class, ResetDemoCommand::class, PreflightCommand::class]);
        }
    }
}
