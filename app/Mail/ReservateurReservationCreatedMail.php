<?php

namespace App\Mail;

use App\Models\ReservateurReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservateurReservationCreatedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public ReservateurReservation $reservation)
    {
        $this->reservation->loadMissing('reservateur');
    }

    public function build(): self
    {
        return $this
            ->subject('SI MD TOURS - Nouvelle reservation reservateur - ' . $this->reservation->reference)
            ->view('emails.reservateurs.reservation-created');
    }
}
