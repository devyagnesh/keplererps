<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * IndiaMART / marketplace lead CSV import (M05).
 *
 * Expected headers: company_name, contact_person, mobile, email, city, requirement, source
 */
class LeadImportService
{
    /**
     * @return array{imported: int, skipped: int}
     */
    public function import(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'Unable to read uploaded file.']);
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'CSV is empty.']);
        }

        $map = [];
        foreach ($header as $i => $col) {
            $map[strtolower(str_replace([' ', '-'], '_', trim((string) $col)))] = $i;
        }

        if (! isset($map['company_name']) && ! isset($map['mobile'])) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'CSV needs company_name or mobile.']);
        }

        $imported = 0;
        $skipped = 0;
        $numbering = app(NumberingService::class);

        while (($data = fgetcsv($handle)) !== false) {
            $company = trim((string) ($data[$map['company_name'] ?? -1] ?? ''));
            $mobile = preg_replace('/\D+/', '', (string) ($data[$map['mobile'] ?? -1] ?? '')) ?: null;
            $email = trim((string) ($data[$map['email'] ?? -1] ?? '')) ?: null;

            if ($company === '' && $mobile === null && $email === null) {
                $skipped++;
                continue;
            }

            $exists = Lead::query()
                ->when($mobile, fn ($q) => $q->where('mobile', $mobile))
                ->when($email && ! $mobile, fn ($q) => $q->where('email', $email))
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $sourceRaw = strtolower((string) ($data[$map['source'] ?? -1] ?? 'indiamart'));
            $source = match (true) {
                str_contains($sourceRaw, 'trade') => LeadSource::TradeIndia,
                str_contains($sourceRaw, 'india') => LeadSource::IndiaMart,
                default => LeadSource::tryFrom($sourceRaw) ?? LeadSource::IndiaMart,
            };

            Lead::query()->create([
                'lead_no' => $numbering->next(DocumentSeriesType::Lead),
                'lead_date' => now()->toDateString(),
                'company_name' => $company !== '' ? $company : ($mobile ?? 'Imported Lead'),
                'contact_person' => trim((string) ($data[$map['contact_person'] ?? $map['contact_name'] ?? -1] ?? '')) ?: 'Contact',
                'mobile' => $mobile ?? '0000000000',
                'email' => $email,
                'city' => trim((string) ($data[$map['city'] ?? -1] ?? '')) ?: null,
                'requirement' => trim((string) ($data[$map['requirement'] ?? $map['product_interest'] ?? -1] ?? '')) ?: null,
                'source' => $source,
                'status' => LeadStatus::New,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
            $imported++;
        }

        fclose($handle);

        return ['imported' => $imported, 'skipped' => $skipped];
    }
}
