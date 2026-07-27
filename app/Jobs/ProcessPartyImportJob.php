<?php

namespace App\Jobs;

use App\Models\PartyImport;
use App\Services\PartyImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued party CSV import processor.
 */
class ProcessPartyImportJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  int  $partyImportId  Import batch primary key.
     */
    public function __construct(public int $partyImportId) {}

    /**
     * Execute the job.
     */
    public function handle(PartyImportService $service): void
    {
        $import = PartyImport::query()->findOrFail($this->partyImportId);

        try {
            $service->process($import);
        } catch (Throwable $e) {
            report($e);
            $import->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            Log::error('Party import failed', [
                'import_id' => $this->partyImportId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
