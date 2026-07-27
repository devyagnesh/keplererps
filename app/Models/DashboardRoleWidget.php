<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Role-specific dashboard widget layout configuration.
 */
class DashboardRoleWidget extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'role_name',
        'widget_keys',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'widget_keys' => 'array',
        ];
    }
}
