<?php

namespace App\Models;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sales enquiry captured before a party record exists (M05).
 *
 * @property int $id
 * @property string $lead_no
 * @property \Illuminate\Support\Carbon $lead_date
 * @property string $company_name
 * @property string $contact_person
 * @property string $mobile
 * @property LeadSource $source
 * @property LeadStatus $status
 * @property int|null $converted_party_id
 */
class Lead extends Model
{
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'lead_no',
        'lead_date',
        'company_name',
        'contact_person',
        'mobile',
        'email',
        'city',
        'state_id',
        'industry',
        'source',
        'status',
        'requirement',
        'estimated_value',
        'next_follow_up_date',
        'assigned_user_id',
        'converted_party_id',
        'converted_at',
        'lost_reason',
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
            'lead_date' => 'date',
            'next_follow_up_date' => 'date',
            'converted_at' => 'datetime',
            'source' => LeadSource::class,
            'status' => LeadStatus::class,
            'estimated_value' => 'decimal:2',
        ];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Party created when the lead was converted.
     */
    public function convertedParty(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'converted_party_id');
    }

    public function opportunities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function followUps(): MorphMany
    {
        return $this->morphMany(CrmFollowUp::class, 'followupable')->latest('follow_up_date');
    }
}
