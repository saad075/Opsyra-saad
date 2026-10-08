<?php

namespace App\Modules\Auth\Notifications;

use App\Modules\Platform\Models\Tenant;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeAdminNotification extends Notification
{
    public function __construct(
        public readonly string $token,
        public readonly Tenant $tenant,
        public readonly int $expiresHours,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim(config('app.frontend_url'), '/').'/reset-password?'.http_build_query([
            'token' => $this->token,
            'client_code' => $this->tenant->client_code,
            'email' => $notifiable->email,
            'welcome' => 1,
        ]);

        $trialEnds = $this->tenant->trial_ends_at?->format('j F Y');

        return (new MailMessage)
            ->subject("Welcome to Opsyra — set up {$this->tenant->name}")
            ->greeting("Welcome, {$notifiable->name}!")
            ->line("Your Opsyra workspace for {$this->tenant->name} is ready.")
            ->line("Your company code is **{$this->tenant->client_code}** — your team uses it to sign in.")
            ->action('Set your password', $url)
            ->line("This link works once and expires in {$this->expiresHours} hours.")
            ->line($trialEnds ? "Your free trial runs until {$trialEnds}." : 'Your free trial has started.')
            ->line('Once you’re in, the setup wizard walks you through the rest.');
    }
}
