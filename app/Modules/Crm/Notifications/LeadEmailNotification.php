<?php

namespace App\Modules\Crm\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A free-text email a staff member composes and sends to a lead's contact —
 * not a system/transactional notification tied to a User. Sent via
 * Notification::route('mail', $toEmail)->notify(...) since the recipient
 * is a plain email address (the lead's contact_email), not a Notifiable
 * model, same MailMessage style as every other email in Opsyra
 * (WelcomeAdminNotification, PasswordResetNotification).
 */
class LeadEmailNotification extends Notification
{
    public function __construct(
        public readonly string $subject,
        public readonly string $body,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)->subject($this->subject);

        foreach (explode("\n", $this->body) as $line) {
            $message->line($line);
        }

        return $message;
    }
}
