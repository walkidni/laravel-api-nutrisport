<?php

namespace App\Domain\Reporting\Jobs;

use App\Domain\Reporting\Actions\BuildDailyReportAction;
use App\Domain\Reporting\Mail\DailyReportMail;
use Illuminate\Support\Facades\Mail;

final class SendDailyReportJob
{
    public function __construct(
        private readonly BuildDailyReportAction $buildDailyReportAction,
    ) {
    }

    public function handle(): void
    {
        Mail::to((string) config('reporting.administrator_email'))
            ->send(new DailyReportMail(($this->buildDailyReportAction)()));
    }
}
