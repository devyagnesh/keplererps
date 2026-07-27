<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Services\EinvoiceService;
use Illuminate\Http\JsonResponse;

/**
 * E-invoice IRN push endpoint.
 */
class EinvoiceController extends Controller
{
    public function __construct(protected EinvoiceService $service) {}

    public function push(SalesInvoice $salesInvoice): JsonResponse
    {
        $result = $this->service->push($salesInvoice);

        return response()->json([
            'status' => true,
            'message' => 'E-invoice '.$result['status'].'.',
            'data' => $result['log'],
        ]);
    }
}
