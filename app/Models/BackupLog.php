<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Manual backup archive log (M16).
 */
class BackupLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'disk_path',
        'size_bytes',
        'status',
        'notes',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
