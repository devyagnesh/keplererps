<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Imported GSTR-2B worksheet snapshot (M13).
 */
class Gstr2bImport extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'period',
        'original_filename',
        'storage_path',
        'row_count',
        'matched_count',
        'mismatch_count',
        'summary',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'summary' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
