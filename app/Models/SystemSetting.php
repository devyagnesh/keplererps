<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Key/value system setting row (M16 foundation).
 *
 * @property int $id
 * @property string $group_key
 * @property string $setting_key
 * @property string|null $setting_value
 * @property string $value_type
 * @property string $label
 * @property bool $is_locked
 */
class SystemSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'group_key',
        'setting_key',
        'setting_value',
        'value_type',
        'label',
        'is_locked',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
        ];
    }

    /**
     * Cast the stored value to its declared type.
     */
    public function typedValue(): mixed
    {
        return match ($this->value_type) {
            'boolean' => filter_var($this->setting_value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->setting_value,
            'json' => json_decode((string) $this->setting_value, true),
            default => $this->setting_value,
        };
    }
}
