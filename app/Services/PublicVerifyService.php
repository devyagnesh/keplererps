<?php

namespace App\Services;

use App\Models\PackageLabel;
use App\Models\QcInspection;

/**
 * Public QR / CoA verification payloads (M17 / M10).
 */
class PublicVerifyService
{
    /**
     * @return array{type: string, data: array<string, mixed>}|null
     */
    public function resolve(string $token): ?array
    {
        $inspection = QcInspection::query()
            ->with(['item:id,item_code,item_name', 'batch:id,batch_no'])
            ->where('public_token', $token)
            ->first();

        if ($inspection !== null) {
            return [
                'type' => 'qc_coa',
                'data' => [
                    'document_no' => $inspection->document_no,
                    'document_date' => $inspection->document_date?->toDateString(),
                    'item_code' => $inspection->item?->item_code,
                    'item_name' => $inspection->item?->item_name,
                    'batch_no' => $inspection->batch?->batch_no,
                    'overall_result' => $inspection->overall_result,
                    'disposition' => $inspection->disposition?->value ?? $inspection->disposition,
                    'completed_at' => $inspection->completed_at?->toDateTimeString(),
                ],
            ];
        }

        $package = PackageLabel::query()
            ->with(['item:id,item_code,item_name', 'batch:id,batch_no'])
            ->where(function ($q) use ($token): void {
                $q->where('label_no', $token)->orWhere('qr_payload', $token);
            })
            ->first();

        if ($package === null) {
            return null;
        }

        return [
            'type' => 'package',
            'data' => [
                'label_no' => $package->label_no,
                'item_code' => $package->item?->item_code,
                'item_name' => $package->item?->item_name,
                'batch_no' => $package->batch?->batch_no,
                'quantity' => (float) $package->quantity,
                'status' => $package->status?->value ?? $package->status,
            ],
        ];
    }
}
