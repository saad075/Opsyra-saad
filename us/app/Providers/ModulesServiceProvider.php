<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Wires up app/Modules/<Name>/... so each module is self-contained: drop a
 * module in with its own database/migrations and routes/api.php and both are
 * picked up automatically, no manual registration per module. Convention:
 *
 *   app/Modules/<Name>/
 *     Models/
 *     Http/Controllers/
 *     database/migrations/
 *     routes/api.php
 *
 * Namespacing needs no extra composer.json entry — App\Modules\<Name>\...
 * already resolves under the existing "App\\": "app/" PSR-4 mapping.
 */
class ModulesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modulesPath = app_path('Modules');

        if (! is_dir($modulesPath)) {
            return;
        }

        foreach (scandir($modulesPath) as $module) {
            if ($module === '.' || $module === '..') {
                continue;
            }

            $modulePath = $modulesPath.DIRECTORY_SEPARATOR.$module;

            if (! is_dir($modulePath)) {
                continue;
            }

            $migrationsPath = $modulePath.'/database/migrations';

            if (is_dir($migrationsPath)) {
                $this->loadMigrationsFrom($migrationsPath);
            }

            $apiRoutes = $modulePath.'/routes/api.php';

            if (file_exists($apiRoutes)) {
                Route::middleware('api')->prefix('api')->group($apiRoutes);
            }
        }
    }
}
