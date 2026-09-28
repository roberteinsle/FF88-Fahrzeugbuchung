<?php

namespace App\Notifications;

use App\Models\BookingDecision;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the requester and to the owners of the conflicting bookings.
 */
class DecisionMadeNotification extends Notification
{
    public function __construct(private readonly BookingDecision $decision) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->decision->booking;
        $approved = $this->decision->status === BookingDecision::STATUS_APPROVED;
        $isRequester = $request->user_id === $notifiable->id;

        $mail = (new MailMessage)
            ->subject('Entscheidung zu '.$request->vehicle->name.' am '.BookingSummary::day($request))
            ->greeting('Hallo '.$notifiable->name.',');

        if ($isRequester) {
            $mail->line($approved
                ? 'deine Anfrage wurde **genehmigt**. Das Fahrzeug ist für dich gebucht.'
                : 'deine Anfrage wurde **abgelehnt**. Die bestehende Buchung bleibt bestehen.');
            $mail->line('**Deine Anfrage:** '.BookingSummary::line($request));
        } else {
            $mail->line($approved
                ? 'zu deiner Buchung gab es eine Konfliktanfrage. Es wurde entschieden, dass die Anfrage Vorrang hat – deine Buchung wurde daher **storniert**.'
                : 'zu deiner Buchung gab es eine Konfliktanfrage. Es wurde entschieden, dass deine Buchung **bestehen bleibt**.');
            $mail->line('**Anfrage von '.$request->user->name.':** '.BookingSummary::line($request));
        }

        $mail->line('**Entschieden von:** '.$this->decision->decider?->name.', '.BookingSummary::dateTime($this->decision->decided_at));

        if ($this->decision->decision_note) {
            $mail->line('**Anmerkung:** '.$this->decision->decision_note);
        }

        return $mail
            ->action('Meine Buchungen', route('my-bookings'))
            ->salutation('Deine Freiwillige Feuerwehr Braak');
    }
}
