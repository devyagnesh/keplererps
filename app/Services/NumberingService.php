<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Models\DocumentNumberSeries;
use App\Models\FinancialYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Central document numbering engine (SRS C2 / architecture Numbering Engine).
 */
class NumberingService
{
    /**
     * Allocate the next document number under a row lock.
     *
     * @throws ValidationException
     */
    public function next(DocumentSeriesType|string $documentType, ?int $branchId = null, ?int $financialYearId = null): string
    {
        $type = $documentType instanceof DocumentSeriesType
            ? $documentType
            : DocumentSeriesType::from((string) $documentType);

        return DB::transaction(function () use ($type, $branchId, $financialYearId): string {
            $fy = $this->resolveFinancialYear($financialYearId);
            if ($fy?->is_closed) {
                throw ValidationException::withMessages([
                    'document_no' => 'Cannot allocate numbers in a closed financial year.',
                ]);
            }

            $series = $this->lockSeries($type, $branchId, $fy?->id);
            $number = $series->formatNumber((int) $series->next_number, $fy?->code);
            $series->forceFill(['next_number' => (int) $series->next_number + 1])->save();

            return $number;
        });
    }

    /**
     * Preview the next number without consuming the sequence.
     */
    public function preview(DocumentSeriesType|string $documentType, ?int $branchId = null, ?int $financialYearId = null): string
    {
        $type = $documentType instanceof DocumentSeriesType
            ? $documentType
            : DocumentSeriesType::from((string) $documentType);

        $fy = $this->resolveFinancialYear($financialYearId);
        $series = $this->findSeries($type, $branchId, $fy?->id);

        if ($series === null) {
            return $type->defaultPrefix().'-'.str_pad('1', 5, '0', STR_PAD_LEFT);
        }

        return $series->formatNumber((int) $series->next_number, $fy?->code);
    }

    protected function lockSeries(DocumentSeriesType $type, ?int $branchId, ?int $financialYearId): DocumentNumberSeries
    {
        $series = $this->findSeriesQuery($type, $branchId, $financialYearId)
            ->lockForUpdate()
            ->first();

        if ($series === null) {
            $series = DocumentNumberSeries::query()->create([
                'document_type' => $type,
                'financial_year_id' => $financialYearId,
                'branch_id' => $branchId,
                'prefix' => $type->defaultPrefix(),
                'separator' => '-',
                'padding' => 5,
                'start_number' => 1,
                'next_number' => 1,
                'include_fy_code' => false,
                'reset_yearly' => true,
                'is_active' => true,
            ]);

            return DocumentNumberSeries::query()->whereKey($series->id)->lockForUpdate()->firstOrFail();
        }

        return $series;
    }

    protected function findSeries(DocumentSeriesType $type, ?int $branchId, ?int $financialYearId): ?DocumentNumberSeries
    {
        return $this->findSeriesQuery($type, $branchId, $financialYearId)->first();
    }

    /**
     * Prefer branch+FY specific series, then FY-only, then global.
     */
    protected function findSeriesQuery(DocumentSeriesType $type, ?int $branchId, ?int $financialYearId)
    {
        $base = DocumentNumberSeries::query()
            ->where('document_type', $type->value)
            ->where('is_active', true);

        if ($branchId !== null && $financialYearId !== null) {
            $specific = (clone $base)
                ->where('branch_id', $branchId)
                ->where('financial_year_id', $financialYearId);
            if ($specific->exists()) {
                return $specific;
            }
        }

        if ($financialYearId !== null) {
            $fyOnly = (clone $base)
                ->whereNull('branch_id')
                ->where('financial_year_id', $financialYearId);
            if ($fyOnly->exists()) {
                return $fyOnly;
            }
        }

        if ($branchId !== null) {
            $branchOnly = (clone $base)
                ->where('branch_id', $branchId)
                ->whereNull('financial_year_id');
            if ($branchOnly->exists()) {
                return $branchOnly;
            }
        }

        return $base->whereNull('branch_id')->whereNull('financial_year_id');
    }

    protected function resolveFinancialYear(?int $financialYearId): ?FinancialYear
    {
        if ($financialYearId !== null) {
            return FinancialYear::query()->find($financialYearId);
        }

        return FinancialYear::query()->where('is_current', true)->first();
    }
}
