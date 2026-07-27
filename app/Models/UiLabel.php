<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Overridable UI label / translation string (C7).
 */
class UiLabel extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'locale',
        'label_key',
        'label_value',
    ];
}
