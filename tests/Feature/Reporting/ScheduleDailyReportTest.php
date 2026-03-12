<?php

namespace Tests\Feature\Reporting;

use App\Domain\Reporting\Jobs\SendDailyReportJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleDailyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_the_daily_report_job_to_run_at_midnight(): void
    {
        $this->artisan('schedule:list')->assertSuccessful();

        $events = app(Schedule::class)->events();

        $dailyReportEvent = collect($events)->first(function ($event): bool {
            return (string) $event->description === SendDailyReportJob::class;
        });

        $this->assertNotNull($dailyReportEvent, 'The daily report job is not registered in the scheduler.');
        $this->assertSame('0 0 * * *', $dailyReportEvent->expression);
        $this->assertNotContains(ShouldQueue::class, class_implements(SendDailyReportJob::class));
    }
}
