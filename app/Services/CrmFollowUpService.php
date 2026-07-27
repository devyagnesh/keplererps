<?php

namespace App\Services;

use App\Models\CrmFollowUp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Shared follow-up log for leads and opportunities (M05).
 */
class CrmFollowUpService
{
    /**
     * Log a follow-up and roll the owner's next follow-up date forward.
     *
     * @param  array<string, mixed>  $data
     */
    public function log(Model $owner, array $data): CrmFollowUp
    {
        return DB::transaction(function () use ($owner, $data): CrmFollowUp {
            $followUp = CrmFollowUp::query()->create([
                'followupable_type' => $owner::class,
                'followupable_id' => $owner->getKey(),
                'follow_up_date' => $data['follow_up_date'] ?? now()->toDateString(),
                'mode' => $data['mode'],
                'summary' => $data['summary'],
                'outcome' => $data['outcome'] ?? null,
                'next_follow_up_date' => $data['next_follow_up_date'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $owner->forceFill([
                'next_follow_up_date' => $followUp->next_follow_up_date,
                'updated_by' => Auth::id(),
            ])->save();

            return $followUp;
        });
    }
}
