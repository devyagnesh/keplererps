<?php

namespace App\Jobs;

use App\Services\ScheduledReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Dispatch due scheduled register report emails.
 */
class RunScheduledReportsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(ScheduledReportService $service): void
    {
        $service->runDue();
    }
}
