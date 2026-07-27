<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherLine;

/**
 * Export posted journal vouchers to Tally-compatible XML.
 */
class TallyExportService
{
    /**
     * Build a simple XML document of posted journal vouchers in a date range.
     */
    public function exportVouchers(string $from, string $to): string
    {
        $vouchers = JournalVoucher::query()
            ->where('status', DocumentStatus::Posted->value)
            ->whereDate('document_date', '>=', $from)
            ->whereDate('document_date', '<=', $to)
            ->with(['lines.ledgerAccount:id,code,name'])
            ->orderBy('document_date')
            ->orderBy('document_no')
            ->get();

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><ENVELOPE></ENVELOPE>');
        $body = $xml->addChild('BODY');
        $importData = $body->addChild('IMPORTDATA');
        $requestData = $importData->addChild('REQUESTDATA');

        foreach ($vouchers as $voucher) {
            $tallyMessage = $requestData->addChild('TALLYMESSAGE');
            $journal = $tallyMessage->addChild('VOUCHER');
            $journal->addAttribute('VCHTYPE', 'Journal');
            $journal->addChild('VOUCHERNUMBER', htmlspecialchars((string) $voucher->document_no));
            $journal->addChild('DATE', $voucher->document_date->format('Ymd'));
            $journal->addChild('NARRATION', htmlspecialchars((string) ($voucher->narration ?? '')));

            $entries = $journal->addChild('ALLLEDGERENTRIES.LIST');

            foreach ($voucher->lines as $line) {
                /** @var JournalVoucherLine $line */
                $ledgerName = $line->ledgerAccount?->name ?? 'Unknown';
                $entry = $entries->addChild('LEDGERENTRY');
                $entry->addChild('LEDGERNAME', htmlspecialchars($ledgerName));

                if ((float) $line->debit > 0) {
                    $entry->addChild('AMOUNT', number_format((float) $line->debit, 2, '.', ''));
                    $entry->addChild('ISDEEMEDPOSITIVE', 'Yes');
                } else {
                    $entry->addChild('AMOUNT', '-'.number_format((float) $line->credit, 2, '.', ''));
                    $entry->addChild('ISDEEMEDPOSITIVE', 'No');
                }
            }
        }

        $document = $xml->asXML();

        return is_string($document) ? $document : '<ENVELOPE></ENVELOPE>';
    }
}
