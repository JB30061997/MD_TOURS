<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DailyPlanningAssignmentsMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $recipientType,
        public CarbonInterface $planningDate,
        public Collection $plannings,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('SI MD TOURS - Planning du ' . $this->planningDate->format('d/m/Y'))
            ->view('emails.plannings.daily-assignments');
    }
}
