<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tracks whether the web installer has completed (cPanel / no-SSH installs).
 */
class InstallLock extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'install_key_hash',
        'is_installed',
        'installed_at',
        'app_version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_installed' => 'boolean',
            'installed_at' => 'datetime',
        ];
    }
}
