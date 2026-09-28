<?php

namespace App\Notifications;

use App\Models\BookingDecision;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DecisionRequestedNotification extends Notification
{
    public function __construct(private readonly BookingDecision $decision) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->decision->booking;

        $mail = (new MailMessage)
            ->subject('Entscheidung nötig: '.$request->vehicle->name.' am '.BookingSummary::day($request))
            ->greeting('Hallo '.$notifiable->name.',')
            ->line('für ein Fahrzeug liegt ein Buchungskonflikt vor. Bitte entscheide, wer das Fahrzeug bekommt.')
            ->line('**Anfrage:** '.BookingSummary::line($request))
            ->line('**Begründung:** '.$this->decision->reason);

        foreach ($this->decision->conflictingBookings() as $existing) {
            $mail->line('**Bestehende Buchung:** '.BookingSummary::line($existing));
        }

        return $mail
            ->action('Zur Entscheidung', route('decisions.show', $this->decision))
            ->line('Sobald jemand entschieden hat, werden alle Beteiligten per E-Mail informiert.')
            ->salutation('Deine Freiwillige Feuerwehr Braak');
    }
}
