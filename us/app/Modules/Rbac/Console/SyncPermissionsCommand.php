<?php

namespace App\Modules\Rbac\Console;

use App\Modules\Rbac\Support\PermissionCatalogue;
use Illuminate\Console\Command;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Create any permissions in the catalogue that are missing';

    public function handle(): int
    {
        PermissionCatalogue::sync();

        $this->info(count(PermissionCatalogue::PERMISSIONS).' permissions in the catalogue are present.');

        return self::SUCCESS;
    }
}
