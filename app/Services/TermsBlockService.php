<?php

namespace App\Services;

use App\Models\TermsBlock;

/**
 * Terms & conditions library (C5).
 */
class TermsBlockService
{
    /**
     * @return \Illuminate\Support\Collection<int, TermsBlock>
     */
    public function all(?string $documentType = null)
    {
        return TermsBlock::query()
            ->when($documentType, fn ($q) => $q->where(function ($inner) use ($documentType): void {
                $inner->whereNull('document_type')->orWhere('document_type', $documentType);
            }))
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TermsBlock
    {
        return TermsBlock::query()->create([
            'code' => strtoupper((string) $data['code']),
            'name' => $data['name'],
            'document_type' => $data['document_type'] ?? null,
            'body' => $data['body'],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    public function delete(int $id): bool
    {
        return (bool) TermsBlock::query()->findOrFail($id)->delete();
    }
}
