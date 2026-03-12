<?php

namespace App\Domain\Reporting\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailyReportMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function build(): self
    {
        return $this;
    }
}
