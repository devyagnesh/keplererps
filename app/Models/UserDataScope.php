<?php

namespace App\Models;

use App\Enums\DataScopeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-user record visibility scope.
 *
 * @property int $id
 * @property int $user_id
 * @property DataScopeType $scope_type
 * @property array<int, int>|null $branch_ids
 * @property array<int, int>|null $warehouse_ids
 * @property array<int, int>|null $team_user_ids
 */
class UserDataScope extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'scope_type',
        'branch_ids',
        'warehouse_ids',
        'team_user_ids',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'scope_type' => 'all',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope_type' => DataScopeType::class,
            'branch_ids' => 'array',
            'warehouse_ids' => 'array',
            'team_user_ids' => 'array',
        ];
    }

    /**
     * Owning user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
