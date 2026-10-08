<?php

namespace App\Console\Commands;

use App\Services\CountryDeployment;
use App\Services\SubscriptionSync;
use Illuminate\Console\Command;
use RuntimeException;

class SyncUsageCommand extends Command
{
    protected $signature = 'companies:sync-usage';

    protected $description = 'Pull each company’s active staff count from its country deployment';

    public function handle(SubscriptionSync $sync): int
    {
        foreach (array_keys(config('opsyra.countries')) as $country) {
            if (! CountryDeployment::isConnected($country)) {
                continue;
            }

            try {
                $this->info("{$country}: ".$sync->pullUsage($country).' companies updated.');
            } catch (RuntimeException $e) {
                report($e);
                $this->error("{$country}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
