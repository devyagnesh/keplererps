<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TallyExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Export posted journal vouchers to Tally XML.
 */
class TallyExportController extends Controller
{
    public function __construct(protected TallyExportService $service) {}

    /**
     * Download posted journal vouchers as Tally-compatible XML.
     */
    public function export(Request $request): Response
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $xml = $this->service->exportVouchers(
            $validated['from'],
            $validated['to']
        );

        $filename = 'tally-export-'.$validated['from'].'-to-'.$validated['to'].'.xml';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
