<?php

namespace App\Notifications;

use App\Filament\Resources\FeedbackThreadResource;
use App\Models\FeedbackThread;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To admins: new feedback or a member replied */
class FeedbackReceivedNotification extends Notification
{
    public function __construct(
        private readonly FeedbackThread $thread,
        private readonly string $body,
        private readonly bool $isNew,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(($this->isNew ? 'Neues Feedback: ' : 'Antwort auf Feedback: ').$this->thread->subject)
            ->greeting('Hallo '.$notifiable->name.',')
            ->line($this->thread->user->name.($this->isNew ? ' hat Feedback geschickt:' : ' hat geantwortet:'))
            ->line('**'.$this->thread->subject.'**')
            ->line($this->body)
            ->action('Im Admin-Bereich antworten', FeedbackThreadResource::getUrl('view', ['record' => $this->thread]))
            ->salutation('Deine Freiwillige Feuerwehr Braak');
    }
}
