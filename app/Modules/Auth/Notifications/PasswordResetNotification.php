<?php

namespace App\Modules\Auth\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Plain for now — branded email templates come with the frontend. The link
 * points at the frontend's reset screen (APP_FRONTEND_URL), which posts the
 * token to /api/auth/reset-password.
 */
class PasswordResetNotification extends Notification
{
    public function __construct(
        public readonly string $token,
        public readonly string $clientCode,
        public readonly int $expiresMinutes,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim(config('app.frontend_url'), '/').'/reset-password?'.http_build_query([
            'token' => $this->token,
            'client_code' => $this->clientCode,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject('Reset your Opsyra password')
            ->line('We received a request to reset your password.')
            ->action('Reset password', $url)
            ->line("This link expires in {$this->expiresMinutes} minutes and can be used once.")
            ->line('If you didn\'t ask for this, you can ignore this email.');
    }
}
