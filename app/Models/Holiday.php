<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Company holiday for leave / attendance calendar (M14).
 */
class Holiday extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'holiday_date',
        'name',
        'is_optional',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'is_optional' => 'boolean',
        ];
    }
}
