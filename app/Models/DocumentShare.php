<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Signed share of a printable commercial document.
 */
class DocumentShare extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'token',
        'document_type',
        'document_id',
        'document_no',
        'channel',
        'recipient',
        'storage_path',
        'pdf_storage_path',
        'public_url',
        'status',
        'meta',
        'created_by',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
