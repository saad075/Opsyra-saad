<?php

namespace App\Modules\Platform\Console;

use App\Modules\Platform\Services\TenantProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class CreateTenantCommand extends Command
{
    protected $signature = 'tenants:create
        {name : Company name}
        {--code= : Client code staff type into the app to find their company}
        {--admin-name= : First administrator\'s name}
        {--admin-email= : First administrator\'s email}
        {--country= : Defaults to this deployment\'s APP_COUNTRY}';

    protected $description = 'Create a company with its defaults and first administrator';

    public function handle(TenantProvisioner $provisioner): int
    {
        $data = [
            'name' => $this->argument('name'),
            'client_code' => $this->option('code'),
            'admin_name' => $this->option('admin-name'),
            'admin_email' => $this->option('admin-email'),
            'country' => $this->option('country'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'client_code' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9][a-z0-9-]*$/'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        try {
            $result = $provisioner->provision(array_filter($data));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Created {$result['tenant']->name} (client code: {$result['tenant']->client_code}, tenant #{$result['tenant']->id}).");
        $this->line("First administrator: {$result['admin']->email}");
        $this->line("Temporary password:  {$result['password']}");
        $this->warn('Shown once. Send it to the administrator securely; they should change it on first login.');

        return self::SUCCESS;
    }
}
