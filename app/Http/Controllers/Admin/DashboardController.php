<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CompanyService;
use App\Services\DashboardService;
use Illuminate\View\View;

/**
 * Admin dashboard landing page with operational widgets (M15).
 */
class DashboardController extends Controller
{
    public function __construct(
        protected CompanyService $companyService,
        protected DashboardService $dashboard
    ) {}

    /**
     * Show the dashboard.
     */
    public function index(): View
    {
        return view('admin.dashboard.index', [
            'company' => $this->companyService->getCompany(),
            'widgets' => $this->dashboard->widgets(),
        ]);
    }
}
