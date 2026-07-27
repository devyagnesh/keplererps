<?php

namespace App\Services;

use App\Enums\PackageStatus;
use App\Models\Batch;
use App\Models\PackageLabel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Batch recall workflow for packing / inventory (M17).
 */
class BatchRecallService
{
    /**
     * Mark a batch recalled and cancel open package labels on that batch.
     */
    public function recall(int $batchId, string $reason): Batch
    {
        return DB::transaction(function () use ($batchId, $reason): Batch {
            $batch = Batch::query()->lockForUpdate()->findOrFail($batchId);

            if ($batch->recalled_at !== null) {
                throw ValidationException::withMessages(['batch' => 'Batch is already recalled.']);
            }

            $batch->forceFill([
                'is_active' => false,
                'recalled_at' => now(),
                'recall_reason' => $reason,
                'recalled_by' => Auth::id(),
            ])->save();

            PackageLabel::query()
                ->where('batch_id', $batch->id)
                ->whereNotIn('status', [PackageStatus::Dispatched->value, PackageStatus::Cancelled->value])
                ->update(['status' => PackageStatus::Cancelled->value]);

            return $batch->fresh();
        });
    }
}
