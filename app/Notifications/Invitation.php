<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email sent to a new person with a link to choose their password.
 */
class Invitation extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        public string $organizationName,
        public ?string $invitedBy = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        return (new MailMessage)
            ->subject("You're invited to {$this->organizationName} on ".config('app.name'))
            ->greeting("Hi {$notifiable->name},")
            ->line(($this->invitedBy ? "{$this->invitedBy} invited you" : 'You have been invited')." to join {$this->organizationName}.")
            ->action('Choose your password', $url)
            ->line('This link expires in '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes. If it expires, use "Forgot your password?" on the login page.');
    }
}
