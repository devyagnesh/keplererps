<?php

namespace App\Services;

use App\Models\PrintTemplate;
use Illuminate\Support\Facades\DB;

/**
 * Print template library (C4).
 */
class PrintTemplateService
{
    /**
     * @return \Illuminate\Support\Collection<int, PrintTemplate>
     */
    public function all(?string $documentType = null)
    {
        return PrintTemplate::query()
            ->when($documentType, fn ($q) => $q->where('document_type', $documentType))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function defaultFor(string $documentType): ?PrintTemplate
    {
        return PrintTemplate::query()
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PrintTemplate
    {
        return DB::transaction(function () use ($data): PrintTemplate {
            if (! empty($data['is_default'])) {
                PrintTemplate::query()
                    ->where('document_type', $data['document_type'])
                    ->update(['is_default' => false]);
            }

            return PrintTemplate::query()->create([
                'code' => strtoupper((string) $data['code']),
                'name' => $data['name'],
                'document_type' => $data['document_type'],
                'header_html' => $data['header_html'] ?? null,
                'footer_html' => $data['footer_html'] ?? null,
                'show_hsn' => (bool) ($data['show_hsn'] ?? true),
                'show_tax_breakup' => (bool) ($data['show_tax_breakup'] ?? true),
                'is_default' => (bool) ($data['is_default'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);
        });
    }

    public function delete(int $id): bool
    {
        return (bool) PrintTemplate::query()->findOrFail($id)->delete();
    }
}
