<?php

namespace App\Models;

use App\Enums\BalanceSide;
use App\Enums\LedgerAccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Chart-of-accounts ledger (M13).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property LedgerAccountType $account_type
 * @property BalanceSide $opening_balance_side
 * @property bool $is_system
 */
class LedgerAccount extends Model
{
    /** @use HasFactory<\Database\Factories\LedgerAccountFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'account_type',
        'account_group',
        'parent_id',
        'party_id',
        'opening_balance',
        'opening_balance_side',
        'is_active',
        'is_system',
        'description',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'account_type' => LedgerAccountType::class,
            'opening_balance' => 'decimal:2',
            'opening_balance_side' => BalanceSide::class,
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalVoucherLine::class);
    }

    /**
     * Signed opening balance in debit-positive terms.
     */
    public function signedOpeningBalance(): float
    {
        $amount = (float) $this->opening_balance;

        return $this->opening_balance_side === BalanceSide::Debit ? $amount : -$amount;
    }
}
