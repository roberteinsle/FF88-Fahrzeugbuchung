<?php

namespace App\Notifications;

use App\Models\BookingDecision;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A decision was taken back: the request waits again and cancelled bookings are active again.
 */
class DecisionReopenedNotification extends Notification
{
    public function __construct(
        private readonly BookingDecision $decision,
        private readonly User $by,
        private readonly ?string $note = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->decision->booking;

        $mail = (new MailMessage)
            ->subject('Entscheidung zurückgenommen: '.$request->vehicle->name.' am '.BookingSummary::day($request))
            ->greeting('Hallo '.$notifiable->name.',')
            ->line($this->by->name.' hat die Entscheidung zu diesem Buchungskonflikt **zurückgenommen**. Die Anfrage ist wieder offen, bestehende Buchungen gelten wieder bis zur neuen Entscheidung.')
            ->line('**Anfrage:** '.BookingSummary::line($request));

        foreach ($this->decision->conflictingBookings() as $existing) {
            $mail->line('**Bestehende Buchung:** '.BookingSummary::line($existing));
        }

        if ($this->note) {
            $mail->line('**Anmerkung:** '.$this->note);
        }

        return $notifiable->canDecide()
            ? $mail->action('Zur Entscheidung', route('decisions.show', $this->decision))->salutation('Deine Freiwillige Feuerwehr Braak')
            : $mail->action('Meine Buchungen', route('my-bookings'))->salutation('Deine Freiwillige Feuerwehr Braak');
    }
}
