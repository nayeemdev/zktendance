<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public ?string $url = null,
        public bool $sendMail = true,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->sendMail && setting('email_notifications') && $notifiable->email
            ? ['database', 'mail']
            : ['database'];
    }

    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->message);

        return $this->url ? $mail->action('Open', $this->url) : $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
