<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Creates a BrainStormers super admin, or resets an existing one's
 * password. The password is shown once and never stored in plain text.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'central:admin {email} {--name= : Required when creating}';

    protected $description = 'Create a Central super admin, or reset their password';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));

        if (Validator::make(['email' => $email], ['email' => 'required|email'])->fails()) {
            $this->error('That is not a valid email address.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if (! $user && ! $this->option('name')) {
            $this->error('Pass --name to create a new admin.');

            return self::FAILURE;
        }

        $password = Str::password(16);

        $user = User::updateOrCreate(['email' => $email], array_filter([
            'name' => $this->option('name') ?? $user?->name,
            'password' => $password,
            'is_active' => true,
        ], fn ($v) => $v !== null));

        $this->info(($user->wasRecentlyCreated ? 'Created' : 'Reset password for')." {$user->email}.");
        $this->line("Password: {$password}");
        $this->warn('Shown once. Store it in your password manager.');

        return self::SUCCESS;
    }
}
