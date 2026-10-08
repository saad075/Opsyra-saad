<?php

namespace App\Modules\Platform\Console;

use App\Modules\Platform\Demo\DemoCompanyBuilder;
use Illuminate\Console\Command;

class ResetDemoCommand extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Delete the public demo company and build it fresh with sample data';

    public function handle(DemoCompanyBuilder $builder): int
    {
        $tenant = $builder->rebuild();

        $this->info("Demo company rebuilt (tenant #{$tenant->id}, code '{$tenant->client_code}').");
        $this->line('Sign in with '.DemoCompanyBuilder::ADMIN_EMAIL.' / '.DemoCompanyBuilder::ADMIN_PASSWORD);

        return self::SUCCESS;
    }
}
