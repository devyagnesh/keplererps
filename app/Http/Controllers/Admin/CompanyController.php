<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompanyUpdateRequest;
use App\Models\State;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Company setup screen (M01 singleton).
 */
class CompanyController extends Controller
{
    public function __construct(
        protected CompanyService $service
    ) {}

    /**
     * Show company settings form.
     */
    public function edit(): View
    {
        return view('admin.company.edit', [
            'company' => $this->service->getCompany(),
            'states' => State::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * Create or update the company singleton.
     */
    public function update(CompanyUpdateRequest $request): JsonResponse
    {
        try {
            $company = $this->service->getCompany();

            if (
                $company !== null
                && $company->has_transactions
                && $request->filled('gstin')
                && $request->input('gstin') !== $company->gstin
                && ! $request->boolean('confirm_gstin_change')
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Changing GSTIN after transactions exist requires confirmation.',
                    'requires_confirmation' => true,
                ], 422);
            }

            $saved = $this->service->save(
                $request->safe()->except(['logo', 'confirm_gstin_change']),
                $request->file('logo')
            );

            return response()->json([
                'status' => true,
                'message' => 'Company details saved successfully.',
                'data' => $saved,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Failed to save company details.',
            ], 500);
        }
    }
}
