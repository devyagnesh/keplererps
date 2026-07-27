<?php

namespace App\Services;

use App\Models\ScanException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Scan exception logging and resolution (warehouse floor).
 */
class ScanExceptionService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function log(
        string $scanCode,
        string $reason,
        string $context = 'package',
        ?string $deviceId = null,
        array $payload = []
    ): ScanException {
        return ScanException::query()->create([
            'scan_code' => $scanCode,
            'context' => $context,
            'reason' => $reason,
            'device_id' => $deviceId,
            'payload' => $payload,
            'status' => 'open',
        ]);
    }

    /**
     * @return Collection<int, ScanException>
     */
    public function open(): Collection
    {
        return ScanException::query()
            ->where('status', 'open')
            ->latest('id')
            ->limit(200)
            ->get();
    }

    public function resolve(int $id): ScanException
    {
        $exception = ScanException::query()->findOrFail($id);
        $exception->update([
            'status' => 'resolved',
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ]);

        return $exception->fresh();
    }
}
