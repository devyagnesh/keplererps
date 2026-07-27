<?php

namespace App\Services;

use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Custom field definitions and values (M16 / C1).
 */
class CustomFieldService
{
    /**
     * @return \Illuminate\Support\Collection<int, CustomFieldDefinition>
     */
    public function definitions(?string $entityType = null)
    {
        return CustomFieldDefinition::query()
            ->when($entityType, fn ($q) => $q->where('entity_type', $entityType))
            ->orderBy('entity_type')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDefinition(array $data): CustomFieldDefinition
    {
        return CustomFieldDefinition::query()->create([
            'entity_type' => strtolower((string) $data['entity_type']),
            'field_key' => strtolower((string) $data['field_key']),
            'label' => $data['label'],
            'field_type' => $data['field_type'] ?? 'text',
            'options' => $data['options'] ?? null,
            'is_required' => (bool) ($data['is_required'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDefinition(int $id, array $data): CustomFieldDefinition
    {
        $definition = CustomFieldDefinition::query()->findOrFail($id);
        $definition->update([
            'label' => $data['label'] ?? $definition->label,
            'field_type' => $data['field_type'] ?? $definition->field_type,
            'options' => array_key_exists('options', $data) ? $data['options'] : $definition->options,
            'is_required' => array_key_exists('is_required', $data) ? (bool) $data['is_required'] : $definition->is_required,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $definition->is_active,
            'sort_order' => array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : $definition->sort_order,
        ]);

        return $definition->fresh();
    }

    public function deleteDefinition(int $id): bool
    {
        return (bool) CustomFieldDefinition::query()->findOrFail($id)->delete();
    }

    /**
     * @param  array<string, mixed>  $values  keyed by field_key
     * @return array<string, string|null>
     */
    public function syncValues(string $entityType, int $entityId, array $values): array
    {
        $definitions = CustomFieldDefinition::query()
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->get()
            ->keyBy('field_key');

        return DB::transaction(function () use ($definitions, $entityType, $entityId, $values): array {
            $stored = [];

            foreach ($definitions as $key => $definition) {
                if ($definition->is_required && (! array_key_exists($key, $values) || $values[$key] === null || $values[$key] === '')) {
                    throw ValidationException::withMessages([
                        "custom_fields.{$key}" => "{$definition->label} is required.",
                    ]);
                }

                if (! array_key_exists($key, $values)) {
                    continue;
                }

                $value = $values[$key] === null ? null : (string) $values[$key];

                CustomFieldValue::query()->updateOrCreate(
                    [
                        'custom_field_definition_id' => $definition->id,
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                    ],
                    ['value' => $value]
                );

                $stored[$key] = $value;
            }

            return $stored;
        });
    }

    /**
     * @return array<string, string|null>
     */
    public function valuesFor(string $entityType, int $entityId): array
    {
        $definitions = $this->definitions($entityType)->where('is_active', true)->keyBy('id');
        $rows = CustomFieldValue::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $definition = $definitions->get($row->custom_field_definition_id);
            if ($definition) {
                $result[$definition->field_key] = $row->value;
            }
        }

        return $result;
    }
}
