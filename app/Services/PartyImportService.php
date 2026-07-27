<?php

namespace App\Services;

use App\Enums\GstType;
use App\Enums\PartyType;
use App\Jobs\ProcessPartyImportJob;
use App\Models\PartyImport;
use App\Models\State;
use App\Rules\IndianMobile;
use App\Rules\IndianPinCode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Party CSV import with dry-run preview and queued commit (US-M01-05).
 */
class PartyImportService
{
    /**
     * Downloadable Excel-compatible CSV template columns.
     *
     * @return list<string>
     */
    public function templateHeaders(): array
    {
        return [
            'party_name',
            'party_type',
            'gst_type',
            'gstin',
            'pan',
            'billing_line1',
            'billing_line2',
            'billing_city',
            'billing_state_code',
            'billing_pin_code',
            'billing_country',
            'credit_limit',
            'unlimited_credit',
            'credit_days',
            'status',
            'contact_name',
            'contact_mobile',
            'contact_email',
            'contact_designation',
            'whatsapp_opt_in',
        ];
    }

    /**
     * Stream a CSV template file.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = $this->templateHeaders();

        return response()->streamDownload(function () use ($headers): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            fputcsv($handle, [
                'Shreeji Traders',
                'customer',
                'unregistered',
                '',
                '',
                'Shop 4 Market Road',
                '',
                'Ahmedabad',
                '24',
                '380001',
                'India',
                '0',
                '0',
                '30',
                'active',
                'Ramesh Patel',
                '9876543210',
                'ramesh@example.com',
                'Owner',
                '1',
            ]);
            fclose($handle);
        }, 'party-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Store upload and run a dry-run validation preview.
     */
    public function preview(UploadedFile $file, int $userId): PartyImport
    {
        $path = $file->store('imports/parties', 'local');
        $rows = $this->readCsv(Storage::disk('local')->path($path));

        if ($rows === []) {
            Storage::disk('local')->delete($path);
            throw ValidationException::withMessages([
                'file' => 'The uploaded file has no data rows.',
            ]);
        }

        $errors = [];
        $valid = 0;
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $validator = Validator::make($row, $this->rowRules());
            if ($validator->fails()) {
                $errors[] = [
                    'row' => $rowNumber,
                    'errors' => $validator->errors()->all(),
                ];
            } else {
                $valid++;
            }
        }

        return PartyImport::query()->create([
            'user_id' => $userId,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'status' => 'previewed',
            'total_rows' => count($rows),
            'valid_rows' => $valid,
            'invalid_rows' => count($errors),
            'preview_errors' => array_slice($errors, 0, 200),
        ]);
    }

    /**
     * Queue the import after a successful preview.
     */
    public function commit(PartyImport $import): PartyImport
    {
        if ($import->status !== 'previewed') {
            throw ValidationException::withMessages([
                'import' => 'Only a previewed import can be committed.',
            ]);
        }

        if ($import->valid_rows < 1) {
            throw ValidationException::withMessages([
                'import' => 'There are no valid rows to import.',
            ]);
        }

        $import->update(['status' => 'processing']);
        ProcessPartyImportJob::dispatch($import->id);

        return $import->fresh();
    }

    /**
     * Process a stored CSV import (called by the queue job).
     */
    public function process(PartyImport $import): void
    {
        /** @var PartyService $partyService */
        $partyService = app(PartyService::class);
        $path = Storage::disk('local')->path($import->stored_path);
        $rows = $this->readCsv($path);
        $states = State::query()->pluck('id', 'code');

        $imported = 0;
        $skipped = 0;
        $errorRows = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $validator = Validator::make($row, $this->rowRules());
            if ($validator->fails()) {
                $skipped++;
                $errorRows[] = array_merge($row, ['errors' => implode('; ', $validator->errors()->all())]);

                continue;
            }

            $stateId = $states[$row['billing_state_code']] ?? null;
            if ($stateId === null) {
                $skipped++;
                $errorRows[] = array_merge($row, ['errors' => 'Unknown billing_state_code.']);

                continue;
            }

            try {
                $partyService->create([
                    'party_name' => $row['party_name'],
                    'party_type' => $row['party_type'],
                    'gst_type' => $row['gst_type'],
                    'gstin' => $row['gstin'] ?: null,
                    'pan' => $row['pan'] ?: null,
                    'billing_line1' => $row['billing_line1'],
                    'billing_line2' => $row['billing_line2'] ?: null,
                    'billing_city' => $row['billing_city'],
                    'billing_state_id' => $stateId,
                    'billing_pin_code' => $row['billing_pin_code'],
                    'billing_country' => $row['billing_country'] ?: 'India',
                    'credit_limit' => $row['credit_limit'] !== '' ? $row['credit_limit'] : 0,
                    'unlimited_credit' => (bool) $row['unlimited_credit'],
                    'credit_days' => $row['credit_days'] !== '' ? (int) $row['credit_days'] : null,
                    'status' => $row['status'] ?: 'active',
                    'contacts' => [[
                        'name' => $row['contact_name'],
                        'mobile' => $row['contact_mobile'],
                        'email' => $row['contact_email'] ?: null,
                        'designation' => $row['contact_designation'] ?: null,
                        'whatsapp_opt_in' => (bool) $row['whatsapp_opt_in'],
                    ]],
                    'addresses' => [],
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
                $errorRows[] = array_merge($row, ['errors' => $e->getMessage()]);
            }
        }

        $errorPath = null;
        if ($errorRows !== []) {
            $errorPath = 'imports/parties/errors/import-'.$import->id.'.csv';
            $this->writeErrorCsv($errorPath, $errorRows);
        }

        $import->update([
            'status' => 'completed',
            'imported_rows' => $imported,
            'skipped_rows' => $skipped,
            'error_file_path' => $errorPath,
            'completed_at' => now(),
        ]);
    }

    /**
     * @return list<array<string, string>>
     */
    protected function readCsv(string $absolutePath): array
    {
        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            return [];
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);

            return [];
        }

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);
        $expected = $this->templateHeaders();
        foreach ($expected as $column) {
            if (! in_array($column, $headers, true)) {
                fclose($handle);
                throw ValidationException::withMessages([
                    'file' => "Missing required column: {$column}",
                ]);
            }
        }

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if ($this->rowIsEmpty($data)) {
                continue;
            }
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = trim((string) ($data[$i] ?? ''));
            }
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @param  list<string|null>  $data
     */
    protected function rowIsEmpty(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rowRules(): array
    {
        return [
            'party_name' => ['required', 'string', 'min:2', 'max:150'],
            'party_type' => ['required', Rule::in(PartyType::values())],
            'gst_type' => ['required', Rule::in(GstType::values())],
            'gstin' => ['nullable', 'string', 'size:15'],
            'billing_line1' => ['required', 'string', 'max:150'],
            'billing_city' => ['required', 'string', 'max:100'],
            'billing_state_code' => ['required', 'string', 'size:2', 'exists:states,code'],
            'billing_pin_code' => ['required', new IndianPinCode],
            'status' => ['required', Rule::in(['active', 'inactive', 'blocked'])],
            'contact_name' => ['required', 'string', 'min:2', 'max:100'],
            'contact_mobile' => ['required', new IndianMobile],
            'contact_email' => ['nullable', 'email'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function writeErrorCsv(string $relativePath, array $rows): void
    {
        $absolute = Storage::disk('local')->path($relativePath);
        $directory = dirname($absolute);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $handle = fopen($absolute, 'w');
        $headers = array_keys($rows[0]);
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }
}
