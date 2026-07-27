<?php

namespace App\Services;

use App\Models\Company;
use App\Repositories\Interfaces\CompanyRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Business logic for the company singleton (M01).
 */
class CompanyService
{
    public function __construct(
        protected CompanyRepositoryInterface $repository,
        protected ActivityLogService $activityLog
    ) {}

    /**
     * Get or bootstrap the company record for the settings screen.
     */
    public function getCompany(): ?Company
    {
        return $this->repository->getSingleton();
    }

    /**
     * Persist company details, enforcing GSTIN/state alignment and lock rules.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function save(array $data, ?UploadedFile $logo = null): Company
    {
        return DB::transaction(function () use ($data, $logo): Company {
            $company = $this->repository->getSingleton();

            if (isset($data['pan'])) {
                $data['pan'] = strtoupper((string) $data['pan']);
            }
            if (isset($data['gstin'])) {
                $data['gstin'] = $data['gstin'] !== null && $data['gstin'] !== ''
                    ? strtoupper((string) $data['gstin'])
                    : null;
            }
            if (isset($data['email'])) {
                $data['email'] = strtolower(trim((string) $data['email']));
            }
            if (isset($data['phone'])) {
                $data['phone'] = preg_replace('/[\s\-]/', '', (string) $data['phone']);
            }
            if (empty($data['trade_name'])) {
                $data['trade_name'] = $data['legal_name'] ?? $company?->legal_name;
            }

            if (! empty($data['is_gst_registered']) && ! empty($data['gstin']) && ! empty($data['state_id'])) {
                $this->assertGstinMatchesState((string) $data['gstin'], (int) $data['state_id']);
            }

            if ($company !== null && $company->has_transactions) {
                unset($data['fy_start_month'], $data['fy_start_day'], $data['base_currency']);
            }

            if ($logo instanceof UploadedFile) {
                $data['logo_path'] = $this->storeLogo($logo, $company?->logo_path);
            }

            if ($company === null) {
                return $this->repository->create($data);
            }

            if (
                $company->has_transactions
                && isset($data['gstin'])
                && $data['gstin'] !== $company->gstin
            ) {
                $this->activityLog->log(
                    event: 'gstin_changed',
                    description: 'Company GSTIN changed after transactions exist.',
                    subject: $company,
                    properties: [
                        'old_gstin' => $company->gstin,
                        'new_gstin' => $data['gstin'],
                    ],
                    logName: 'company'
                );
            }

            return $this->repository->update($company, $data);
        });
    }

    /**
     * Ensure GSTIN state code matches the selected state master code.
     *
     * @throws ValidationException
     */
    protected function assertGstinMatchesState(string $gstin, int $stateId): void
    {
        $state = $this->repository->activeStates()->firstWhere('id', $stateId);
        if ($state === null) {
            throw ValidationException::withMessages([
                'state_id' => 'Selected state is invalid.',
            ]);
        }

        if (substr($gstin, 0, 2) !== $state->code) {
            throw ValidationException::withMessages([
                'state_id' => 'State does not match GSTIN.',
            ]);
        }
    }

    /**
     * Store a company logo and remove the previous file when replaced.
     */
    protected function storeLogo(UploadedFile $logo, ?string $previousPath): string
    {
        if ($previousPath) {
            Storage::disk('public')->delete($previousPath);
        }

        return $logo->store('company', 'public');
    }
}
