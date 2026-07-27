<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit row for an e-invoice IRN push / dry-run.
 */
class EinvoiceLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'sales_invoice_id',
        'status',
        'irn',
        'ack_no',
        'payload',
        'response',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response' => 'array',
        ];
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
