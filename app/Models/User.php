<?php

namespace App\Models;

use App\Models\Concerns\HasRolesAndPermissions;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Application user for the admin ERP panel.
 *
 * @property int $id
 * @property string $name
 * @property string|null $username
 * @property string $email
 * @property string|null $mobile
 * @property string $password
 * @property bool $is_active
 * @property bool $must_change_password
 * @property bool $require_2fa
 * @property \Illuminate\Support\Carbon|null $valid_from
 * @property \Illuminate\Support\Carbon|null $valid_until
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property int|null $branch_id
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRolesAndPermissions, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'mobile',
        'password',
        'is_active',
        'must_change_password',
        'require_2fa',
        'valid_from',
        'valid_until',
        'last_login_at',
        'branch_id',
        'party_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'must_change_password' => false,
        'require_2fa' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'require_2fa' => 'boolean',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Default branch scope for the user.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Linked customer party for portal users.
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /**
     * Record visibility scope.
     */
    public function dataScope(): HasOne
    {
        return $this->hasOne(UserDataScope::class);
    }

    /**
     * Whether the user may log in right now.
     */
    public function canAuthenticate(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->startOfDay();

        if ($this->valid_from !== null && $today->lt($this->valid_from->startOfDay())) {
            return false;
        }

        if ($this->valid_until !== null && $today->gt($this->valid_until->startOfDay())) {
            return false;
        }

        return true;
    }
}
