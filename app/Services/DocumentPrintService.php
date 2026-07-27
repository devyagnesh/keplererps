<?php

namespace App\Services;

use App\Models\Company;
use App\Models\DeliveryChallan;
use App\Models\Party;
use App\Models\PrintTemplate;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\SalesQuotation;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Builds the print payload for commercial documents (US-M07-02, US-M06-03, US-M12-02).
 *
 * All four documents share one Blade template; this service normalises each document
 * into the same view contract so the layout stays DRY.
 *
 * @phpstan-type PrintRow array{
 *     description: string,
 *     hsn: string|null,
 *     quantity: float,
 *     uom: string|null,
 *     rate: float,
 *     discount: float|null,
 *     taxable: float|null,
 *     gst_rate: float|null,
 *     tax: float|null,
 *     total: float
 * }
 */
class DocumentPrintService
{
    public function __construct(protected DocumentPdfService $pdf) {}

    /** Number words used when spelling out the grand total (Indian numbering). */
    protected const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen',
        'Eighteen', 'Nineteen',
    ];

    /** @var list<string> */
    protected const TENS = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    /**
     * @return array<string, mixed>
     */
    public function purchaseOrder(int $id): array
    {
        $document = PurchaseOrder::query()
            ->with(['supplier.billingState', 'warehouse', 'items.item.hsnCode', 'items.uom'])
            ->findOrFail($id);

        $rows = $document->items->map(fn ($line): array => [
            'description' => $this->lineDescription($line),
            'hsn' => $line->item?->hsnCode?->code,
            'quantity' => (float) $line->quantity,
            'uom' => $line->uom?->code,
            'rate' => (float) $line->rate,
            'discount' => null,
            'taxable' => round((float) $line->quantity * (float) $line->rate, 2),
            'gst_rate' => (float) $line->gst_rate,
            'tax' => (float) $line->tax_amount,
            'total' => (float) $line->line_total,
        ])->all();

        return $this->payload(
            title: 'Purchase Order',
            document: $document,
            partyHeading: 'Supplier',
            party: $document->supplier,
            meta: [
                'Order No' => $document->document_no,
                'Order Date' => $document->document_date?->format('d-m-Y'),
                'Expected Delivery' => $document->expected_delivery_date?->format('d-m-Y'),
                'Deliver To' => $document->warehouse?->name,
                'Status' => $document->status->label(),
            ],
            rows: $rows,
            totals: [
                'Taxable Value' => (float) $document->subtotal,
                'Tax' => (float) $document->tax_total,
                'Grand Total' => (float) $document->grand_total,
            ],
            grandTotal: (float) $document->grand_total,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function salesQuotation(int $id): array
    {
        $document = SalesQuotation::query()
            ->with(['customer.billingState', 'items.item.hsnCode', 'items.uom'])
            ->findOrFail($id);

        return $this->payload(
            title: 'Quotation',
            document: $document,
            partyHeading: 'Customer',
            party: $document->customer,
            meta: [
                'Quotation No' => $document->document_no,
                'Date' => $document->document_date?->format('d-m-Y'),
                'Valid Until' => $document->valid_until?->format('d-m-Y'),
                'Status' => $document->status->label(),
            ],
            rows: $this->salesRows($document->items),
            totals: [
                'Taxable Value' => (float) $document->subtotal,
                'Discount' => (float) $document->discount_total,
                'Tax' => (float) $document->tax_total,
                'Grand Total' => (float) $document->grand_total,
            ],
            grandTotal: (float) $document->grand_total,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function salesInvoice(int $id): array
    {
        $document = SalesInvoice::query()
            ->with(['customer.billingState', 'items.item.hsnCode', 'items.uom', 'placeOfSupplyState'])
            ->findOrFail($id);

        return $this->payload(
            title: 'Tax Invoice',
            document: $document,
            partyHeading: 'Bill To',
            party: $document->customer,
            meta: [
                'Invoice No' => $document->document_no,
                'Invoice Date' => $document->document_date?->format('d-m-Y'),
                'Place of Supply' => $document->placeOfSupplyState?->name,
                'Tax Type' => strtoupper((string) ($document->tax_type instanceof \BackedEnum ? $document->tax_type->value : $document->tax_type)),
                'Status' => $document->status->label(),
            ],
            rows: $this->salesRows($document->items),
            totals: [
                'Taxable Value' => (float) $document->subtotal,
                'Discount' => (float) $document->discount_total,
                'Tax' => (float) $document->tax_total,
                'Round Off' => (float) $document->round_off,
                'Grand Total' => (float) $document->grand_total,
            ],
            grandTotal: (float) $document->grand_total,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function deliveryChallan(int $id): array
    {
        $document = DeliveryChallan::query()
            ->with(['customer.billingState', 'items.item.hsnCode', 'items.uom', 'items.batch', 'warehouse', 'transporter'])
            ->findOrFail($id);

        $rows = $document->items->map(fn ($line): array => [
            'description' => $this->lineDescription($line).($line->batch ? ' (Batch '.$line->batch->batch_no.')' : ''),
            'hsn' => $line->item?->hsnCode?->code,
            'quantity' => (float) $line->quantity,
            'uom' => $line->uom?->code,
            'rate' => (float) $line->rate,
            'discount' => null,
            'taxable' => null,
            'gst_rate' => null,
            'tax' => null,
            'total' => round((float) $line->quantity * (float) $line->rate, 2),
        ])->all();

        return $this->payload(
            title: 'Delivery Challan',
            document: $document,
            partyHeading: 'Ship To',
            party: $document->customer,
            meta: [
                'Challan No' => $document->document_no,
                'Date' => $document->document_date?->format('d-m-Y'),
                'Dispatched From' => $document->warehouse?->name,
                'Transport Mode' => $document->transport_mode?->label(),
                'Vehicle' => $document->vehicle_number,
                'Transporter' => $document->transporter?->name,
                'LR No' => $document->lr_number,
                'Packages' => $document->number_of_packages,
                'E-way Bill' => $document->eway_bill_number,
            ],
            rows: $rows,
            totals: [
                'Dispatch Value' => (float) $document->dispatch_value,
            ],
            grandTotal: (float) $document->dispatch_value,
            showTaxColumns: false,
        );
    }

    /**
     * Spell an amount in Indian numbering, e.g. "One Lakh Two Thousand Rupees and Fifty Paise Only".
     */
    public function amountInWords(float $amount): string
    {
        $rupees = (int) floor(abs($amount));
        $paise = (int) round((abs($amount) - $rupees) * 100);

        $words = $rupees === 0 ? 'Zero' : trim($this->spellIndian($rupees));
        $text = $words.' Rupees';

        if ($paise > 0) {
            $text .= ' and '.trim($this->spellIndian($paise)).' Paise';
        }

        return $text.' Only';
    }

    /**
     * @param  iterable<object>  $items
     * @return list<array<string, mixed>>
     */
    protected function salesRows(iterable $items): array
    {
        $rows = [];

        foreach ($items as $line) {
            $rows[] = [
                'description' => $this->lineDescription($line),
                'hsn' => $line->item?->hsnCode?->code,
                'quantity' => (float) $line->quantity,
                'uom' => $line->uom?->code,
                'rate' => (float) $line->rate,
                'discount' => (float) $line->discount_amount,
                'taxable' => (float) $line->taxable_amount,
                'gst_rate' => (float) $line->gst_rate,
                'tax' => (float) $line->tax_amount,
                'total' => (float) $line->line_total,
            ];
        }

        return $rows;
    }

    protected function lineDescription(object $line): string
    {
        $item = $line->item;
        $name = trim(($item?->item_code ? $item->item_code.' — ' : '').($item?->item_name ?? ''));
        $name = $name !== '' ? $name : '—';
        $note = $line->description ?? null;

        return trim($name.($note ? ' ('.$note.')' : ''));
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, float>  $totals
     * @return array<string, mixed>
     */
    protected function payload(
        string $title,
        Model $document,
        string $partyHeading,
        ?Party $party,
        array $meta,
        array $rows,
        array $totals,
        float $grandTotal,
        bool $showTaxColumns = true
    ): array {
        return [
            'title' => $title,
            'document' => $document,
            'company' => Company::query()->with('state')->first(),
            'partyHeading' => $partyHeading,
            'party' => $party,
            'meta' => array_filter($meta, fn ($value): bool => $value !== null && $value !== ''),
            'rows' => $rows,
            'totals' => $totals,
            'amountInWords' => $this->amountInWords($grandTotal),
            'showTaxColumns' => $showTaxColumns,
        ];
    }

    /**
     * Spell a non-negative integer using the Indian crore/lakh grouping.
     */
    protected function spellIndian(int $number): string
    {
        if ($number < 20) {
            return self::ONES[$number];
        }

        if ($number < 100) {
            return self::TENS[intdiv($number, 10)].($number % 10 ? ' '.self::ONES[$number % 10] : '');
        }

        foreach ([10000000 => 'Crore', 100000 => 'Lakh', 1000 => 'Thousand', 100 => 'Hundred'] as $divisor => $label) {
            if ($number >= $divisor) {
                $remainder = $number % $divisor;

                return trim($this->spellIndian(intdiv($number, $divisor)).' '.$label.($remainder ? ' '.$this->spellIndian($remainder) : ''));
            }
        }

        return self::ONES[$number];
    }

    /**
     * Render HTML or DomPDF for a prepared print payload (C4 + §8).
     *
     * @param  array<string, mixed>  $payload
     */
    public function respond(array $payload, string $documentType, bool $asPdf = false): SymfonyResponse
    {
        $template = PrintTemplate::query()
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->first();

        if ($template !== null) {
            $payload['header_html'] = $template->header_html;
            $payload['footer_html'] = $template->footer_html;
            $payload['show_hsn'] = (bool) $template->show_hsn;
            $payload['show_tax_breakup'] = (bool) $template->show_tax_breakup;
        }

        $html = view('admin.print.document', $payload)->render();

        if (! $asPdf) {
            return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        $title = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($payload['title'] ?? $documentType)) ?: $documentType;

        return response($this->pdf->fromHtml($html), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$title.'.pdf"',
        ]);
    }
}
