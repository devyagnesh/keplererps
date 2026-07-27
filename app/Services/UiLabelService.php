<?php

namespace App\Services;

use App\Models\UiLabel;
use Illuminate\Support\Facades\Cache;

/**
 * UI label overrides / localisation keys (C7).
 */
class UiLabelService
{
    /**
     * @return array<string, string>
     */
    public function map(string $locale = 'en'): array
    {
        return Cache::remember("ui_labels.{$locale}", 300, function () use ($locale): array {
            return UiLabel::query()
                ->where('locale', $locale)
                ->pluck('label_value', 'label_key')
                ->all();
        });
    }

    public function get(string $key, ?string $default = null, string $locale = 'en'): string
    {
        $map = $this->map($locale);
        if (isset($map[$key])) {
            return $map[$key];
        }

        $translated = __("erp.{$key}", [], $locale);
        if ($translated !== "erp.{$key}") {
            return $translated;
        }

        return $default ?? $key;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsert(array $data): UiLabel
    {
        $label = UiLabel::query()->updateOrCreate(
            [
                'locale' => $data['locale'] ?? 'en',
                'label_key' => $data['label_key'],
            ],
            ['label_value' => $data['label_value']]
        );

        Cache::forget('ui_labels.'.($data['locale'] ?? 'en'));

        return $label;
    }

    /**
     * @return \Illuminate\Support\Collection<int, UiLabel>
     */
    public function all(string $locale = 'en')
    {
        return UiLabel::query()->where('locale', $locale)->orderBy('label_key')->get();
    }
}
