<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks a party CSV import batch (preview + queued commit).
 *
 * @property int $id
 * @property int $user_id
 * @property string $original_filename
 * @property string $stored_path
 * @property string $status
 * @property int $total_rows
 * @property int $valid_rows
 * @property int $invalid_rows
 * @property int $imported_rows
 * @property int $skipped_rows
 * @property string|null $error_file_path
 * @property array<int, array<string, mixed>>|null $preview_errors
 * @property string|null $failure_reason
 * @property \Illuminate\Support\Carbon|null $completed_at
 */
class PartyImport extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'original_filename',
        'stored_path',
        'status',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'imported_rows',
        'skipped_rows',
        'error_file_path',
        'preview_errors',
        'failure_reason',
        'completed_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'total_rows' => 0,
        'valid_rows' => 0,
        'invalid_rows' => 0,
        'imported_rows' => 0,
        'skipped_rows' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preview_errors' => 'array',
            'completed_at' => 'datetime',
            'total_rows' => 'integer',
            'valid_rows' => 'integer',
            'invalid_rows' => 'integer',
            'imported_rows' => 'integer',
            'skipped_rows' => 'integer',
        ];
    }

    /**
     * User who started the import.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
