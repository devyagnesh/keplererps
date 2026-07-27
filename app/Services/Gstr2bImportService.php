<?php

namespace App\Services;

use App\Enums\PurchaseBillStatus;
use App\Models\Gstr2bImport;
use App\Models\PurchaseBill;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * GSTR-2B CSV import and ITC mismatch report (M13).
 *
 * Expected CSV headers: gstin, invoice_no, invoice_date, taxable_value, igst, cgst, sgst
 */
class Gstr2bImportService
{
    /**
     * @return array{import: Gstr2bImport, mismatches: list<array<string, mixed>>}
     */
    public function import(UploadedFile $file, string $period): array
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            throw ValidationException::withMessages(['period' => 'Period must be YYYY-MM.']);
        }

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'Unable to read uploaded file.']);
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => 'CSV is empty.']);
        }

        $map = $this->headerMap($header);
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count(array_filter($data, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }
            $rows[] = [
                'gstin' => strtoupper(trim((string) ($data[$map['gstin']] ?? ''))),
                'invoice_no' => trim((string) ($data[$map['invoice_no']] ?? '')),
                'invoice_date' => trim((string) ($data[$map['invoice_date']] ?? '')),
                'taxable_value' => (float) ($data[$map['taxable_value']] ?? 0),
                'igst' => (float) ($data[$map['igst']] ?? 0),
                'cgst' => (float) ($data[$map['cgst']] ?? 0),
                'sgst' => (float) ($data[$map['sgst']] ?? 0),
            ];
        }
        fclose($handle);

        $path = $file->storeAs('gstr2b', now()->format('Ymd_His').'_'.$file->getClientOriginalName(), 'local');
        $mismatches = $this->matchRows($rows, $period);

        $import = Gstr2bImport::query()->create([
            'period' => $period,
            'original_filename' => $file->getClientOriginalName(),
            'storage_path' => $path,
            'row_count' => count($rows),
            'matched_count' => count($rows) - count($mismatches),
            'mismatch_count' => count($mismatches),
            'summary' => [
                'mismatches' => array_slice($mismatches, 0, 100),
            ],
            'created_by' => Auth::id(),
        ]);

        return ['import' => $import, 'mismatches' => $mismatches];
    }

    /**
     * @param  list<string|null>  $header
     * @return array{gstin: int, invoice_no: int, invoice_date: int, taxable_value: int, igst: int, cgst: int, sgst: int}
     */
    protected function headerMap(array $header): array
    {
        $normalized = [];
        foreach ($header as $i => $col) {
            $key = strtolower(trim((string) $col));
            $key = str_replace([' ', '-'], '_', $key);
            $normalized[$key] = $i;
        }

        $required = ['gstin', 'invoice_no', 'invoice_date', 'taxable_value'];
        foreach ($required as $field) {
            if (! array_key_exists($field, $normalized)) {
                throw ValidationException::withMessages([
                    'file' => "CSV must include column {$field}.",
                ]);
            }
        }

        return [
            'gstin' => $normalized['gstin'],
            'invoice_no' => $normalized['invoice_no'],
            'invoice_date' => $normalized['invoice_date'],
            'taxable_value' => $normalized['taxable_value'],
            'igst' => $normalized['igst'] ?? -1,
            'cgst' => $normalized['cgst'] ?? -1,
            'sgst' => $normalized['sgst'] ?? -1,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function matchRows(array $rows, string $period): array
    {
        [$year, $month] = array_map('intval', explode('-', $period));
        $bills = PurchaseBill::query()
            ->with(['supplier:id,gstin,party_name', 'items'])
            ->where('status', PurchaseBillStatus::Approved)
            ->whereYear('document_date', $year)
            ->whereMonth('document_date', $month)
            ->get();

        $byKey = [];
        foreach ($bills as $bill) {
            $gstin = strtoupper(trim((string) ($bill->supplier?->gstin ?? '')));
            $inv = strtoupper(trim((string) ($bill->supplier_bill_no ?? $bill->document_no)));
            $byKey[$gstin.'|'.$inv] = $bill;
        }

        $mismatches = [];
        foreach ($rows as $row) {
            $key = $row['gstin'].'|'.strtoupper($row['invoice_no']);
            $bill = $byKey[$key] ?? null;
            if ($bill === null) {
                $mismatches[] = array_merge($row, ['reason' => 'not_in_books']);
                continue;
            }

            $taxable = round((float) $bill->items->sum(fn ($l) => (float) $l->taxable_amount), 2);
            if (abs($taxable - (float) $row['taxable_value']) > 1) {
                $mismatches[] = array_merge($row, [
                    'reason' => 'taxable_mismatch',
                    'books_taxable' => $taxable,
                ]);
            }
        }

        return $mismatches;
    }
}
