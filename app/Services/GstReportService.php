<?php

namespace App\Services;

use App\Enums\PurchaseBillStatus;
use App\Enums\SalesInvoiceStatus;
use App\Models\Company;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * GSTR-1 / GSTR-3B worksheets built from confirmed sales invoices and approved purchase bills.
 *
 * These are reconciliation worksheets for the accountant, not GSP filings.
 */
class GstReportService
{
    public function __construct(protected CsvExportService $csv) {}

    /**
     * Outward supply worksheet rows for a period (GSTR-1 style).
     *
     * @return list<array<string, mixed>>
     */
    public function outwardSupplies(string $fromDate, string $toDate): array
    {
        $invoices = SalesInvoice::query()
            ->with([
                'items',
                'customer:id,party_code,party_name,gstin',
                'placeOfSupplyState:id,code,name',
            ])
            ->where('status', SalesInvoiceStatus::Confirmed)
            ->whereDate('document_date', '>=', $fromDate)
            ->whereDate('document_date', '<=', $toDate)
            ->orderBy('document_date')
            ->orderBy('id')
            ->get();

        $rows = [];

        foreach ($invoices as $invoice) {
            $gstin = trim((string) ($invoice->customer?->gstin ?? ''));

            $rows[] = [
                'section' => $gstin !== '' ? 'B2B' : 'B2C',
                'gstin' => $gstin !== '' ? $gstin : 'URP',
                'party_name' => $invoice->customer?->party_name,
                'invoice_no' => $invoice->document_no,
                'invoice_date' => $invoice->document_date?->toDateString(),
                'place_of_supply' => trim(($invoice->placeOfSupplyState?->code ?? '').' '.($invoice->placeOfSupplyState?->name ?? '')),
                'taxable_value' => round((float) $invoice->items->sum(fn ($line) => (float) $line->taxable_amount), 2),
                'cgst' => round((float) $invoice->items->sum(fn ($line) => (float) $line->cgst_amount), 2),
                'sgst' => round((float) $invoice->items->sum(fn ($line) => (float) $line->sgst_amount), 2),
                'igst' => round((float) $invoice->items->sum(fn ($line) => (float) $line->igst_amount), 2),
                'invoice_value' => round((float) $invoice->grand_total, 2),
            ];
        }

        return $rows;
    }

    /**
     * Inward supply worksheet rows (input tax credit) for a period.
     *
     * @return list<array<string, mixed>>
     */
    public function inwardSupplies(string $fromDate, string $toDate): array
    {
        $bills = PurchaseBill::query()
            ->with(['items', 'supplier:id,party_code,party_name,gstin,billing_state_id'])
            ->where('status', PurchaseBillStatus::Approved)
            ->whereDate('document_date', '>=', $fromDate)
            ->whereDate('document_date', '<=', $toDate)
            ->orderBy('document_date')
            ->orderBy('id')
            ->get();

        $companyStateId = (int) (Company::query()->value('state_id') ?? 0);
        $rows = [];

        foreach ($bills as $bill) {
            $taxable = round((float) $bill->items->sum(fn ($line) => (float) $line->taxable_amount), 2);
            $tax = round((float) $bill->items->sum(fn ($line) => (float) $line->tax_amount), 2);
            $supplierStateId = (int) ($bill->supplier?->billing_state_id ?? 0);
            $isIntraState = $companyStateId === 0 || $supplierStateId === 0 || $companyStateId === $supplierStateId;
            $half = $isIntraState ? round($tax / 2, 2) : 0.0;

            $rows[] = [
                'gstin' => trim((string) ($bill->supplier?->gstin ?? '')) ?: 'URP',
                'party_name' => $bill->supplier?->party_name,
                'bill_no' => $bill->supplier_bill_no,
                'bill_date' => $bill->supplier_bill_date?->toDateString(),
                'document_no' => $bill->document_no,
                'taxable_value' => $taxable,
                'cgst' => $half,
                'sgst' => $isIntraState ? round($tax - $half, 2) : 0.0,
                'igst' => $isIntraState ? 0.0 : $tax,
                'bill_value' => round((float) $bill->grand_total, 2),
            ];
        }

        return $rows;
    }

    /**
     * GSTR-3B style summary: outward tax, input credit and the net payable.
     *
     * @return array<string, mixed>
     */
    public function summary(string $fromDate, string $toDate): array
    {
        $outward = $this->outwardSupplies($fromDate, $toDate);
        $inward = $this->inwardSupplies($fromDate, $toDate);

        $outwardTotals = $this->totals($outward, 'taxable_value');
        $inwardTotals = $this->totals($inward, 'taxable_value');

        $netCgst = round($outwardTotals['cgst'] - $inwardTotals['cgst'], 2);
        $netSgst = round($outwardTotals['sgst'] - $inwardTotals['sgst'], 2);
        $netIgst = round($outwardTotals['igst'] - $inwardTotals['igst'], 2);

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'outward' => $outwardTotals,
            'inward' => $inwardTotals,
            'net_payable' => [
                'cgst' => $netCgst,
                'sgst' => $netSgst,
                'igst' => $netIgst,
                'total' => round($netCgst + $netSgst + $netIgst, 2),
            ],
            'b2b_count' => count(array_filter($outward, fn (array $row): bool => $row['section'] === 'B2B')),
            'b2c_count' => count(array_filter($outward, fn (array $row): bool => $row['section'] === 'B2C')),
        ];
    }

    /**
     * Stream a worksheet as CSV.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function streamCsv(array $rows, string $filename): StreamedResponse
    {
        return $this->csv->stream($rows, $filename);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{taxable_value: float, cgst: float, sgst: float, igst: float, tax_total: float, count: int}
     */
    protected function totals(array $rows, string $taxableKey): array
    {
        $taxable = round(array_sum(array_column($rows, $taxableKey)), 2);
        $cgst = round(array_sum(array_column($rows, 'cgst')), 2);
        $sgst = round(array_sum(array_column($rows, 'sgst')), 2);
        $igst = round(array_sum(array_column($rows, 'igst')), 2);

        return [
            'taxable_value' => $taxable,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => $igst,
            'tax_total' => round($cgst + $sgst + $igst, 2),
            'count' => count($rows),
        ];
    }
}
