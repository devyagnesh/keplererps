<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single debit or credit leg of a journal voucher.
 *
 * @property int $id
 * @property int $journal_voucher_id
 * @property int $ledger_account_id
 */
class JournalVoucherLine extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'journal_voucher_id',
        'ledger_account_id',
        'party_id',
        'debit',
        'credit',
        'narration',
        'sort_order',
        'reconciled_at',
        'bank_date',
        'reconciled_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'reconciled_at' => 'datetime',
            'bank_date' => 'date',
        ];
    }

    public function journalVoucher(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class);
    }

    /**
     * Alias used by bank reconciliation screens.
     */
    public function voucher(): BelongsTo
    {
        return $this->journalVoucher();
    }

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
