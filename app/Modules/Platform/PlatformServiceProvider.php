<?php

namespace App\Modules\Platform;

use App\Modules\Platform\Console\CreateTenantCommand;
use App\Modules\Platform\Console\PreflightCommand;
use App\Modules\Platform\Console\ResetDemoCommand;
use App\Modules\Platform\Support\MailHealth;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class PlatformServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([CreateTenantCommand::class, ResetDemoCommand::class, PreflightCommand::class]);
        }

        // Failures are recorded where they're reported (bootstrap/app.php).
        Event::listen(MessageSent::class, fn () => MailHealth::recordSent());
    }
}
