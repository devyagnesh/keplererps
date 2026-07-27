<?php

namespace App\Models;

use App\Enums\OpportunityStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Qualified sales opportunity tracked through a stage pipeline (M05).
 *
 * @property int $id
 * @property string $opportunity_no
 * @property \Illuminate\Support\Carbon $opportunity_date
 * @property string $title
 * @property OpportunityStage $stage
 * @property int|null $party_id
 * @property int|null $quotation_id
 */
class Opportunity extends Model
{
    /** @use HasFactory<\Database\Factories\OpportunityFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'opportunity_no',
        'opportunity_date',
        'title',
        'lead_id',
        'party_id',
        'stage',
        'expected_value',
        'probability_percent',
        'expected_close_date',
        'next_follow_up_date',
        'assigned_user_id',
        'quotation_id',
        'lost_reason',
        'closed_at',
        'remarks',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opportunity_date' => 'date',
            'expected_close_date' => 'date',
            'next_follow_up_date' => 'date',
            'closed_at' => 'datetime',
            'stage' => OpportunityStage::class,
            'expected_value' => 'decimal:2',
            'probability_percent' => 'integer',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Quotation raised for this opportunity.
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class, 'quotation_id');
    }

    public function followUps(): MorphMany
    {
        return $this->morphMany(CrmFollowUp::class, 'followupable')->latest('follow_up_date');
    }

    /**
     * Pipeline value weighted by the win probability.
     */
    public function weightedValue(): float
    {
        return round((float) $this->expected_value * ((int) $this->probability_percent / 100), 2);
    }
}
