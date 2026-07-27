<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\CustomerPortalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Portal dashboard and document lists.
 */
class PortalDashboardController extends Controller
{
    public function __construct(protected CustomerPortalService $portal) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('portal.dashboard', [
            'party' => $this->portal->partyFor($user),
            'orders' => $this->portal->orders($user),
            'invoices' => $this->portal->invoices($user),
        ]);
    }
}
