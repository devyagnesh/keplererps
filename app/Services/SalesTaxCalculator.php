<?php

namespace App\Services;

use App\Models\Company;

/**
 * GST line/header tax helpers for sales documents (US-M06-05).
 */
class SalesTaxCalculator
{
    /**
     * @return array{tax_type: string, company_state_id: int|null}
     */
    public function resolveContext(int $placeOfSupplyStateId): array
    {
        $company = Company::query()->first();
        $companyStateId = $company?->state_id;

        $taxType = ($companyStateId !== null && (int) $companyStateId === $placeOfSupplyStateId)
            ? 'cgst_sgst'
            : 'igst';

        return [
            'tax_type' => $taxType,
            'company_state_id' => $companyStateId,
        ];
    }

    /**
     * @return array{
     *     discount_amount: float,
     *     taxable_amount: float,
     *     cgst_amount: float,
     *     sgst_amount: float,
     *     igst_amount: float,
     *     tax_amount: float,
     *     line_total: float
     * }
     */
    public function calculateLine(
        float $quantity,
        float $rate,
        float $discountPercent,
        float $gstRate,
        string $taxType
    ): array {
        $gross = round($quantity * $rate, 2);
        $discountAmount = round($gross * ($discountPercent / 100), 2);
        $taxable = round(max(0, $gross - $discountAmount), 2);

        $cgst = 0.0;
        $sgst = 0.0;
        $igst = 0.0;

        if ($taxType === 'igst') {
            $igst = round($taxable * ($gstRate / 100), 2);
        } else {
            $half = round($taxable * ($gstRate / 200), 2);
            $cgst = $half;
            $sgst = $half;
        }

        $tax = round($cgst + $sgst + $igst, 2);

        return [
            'discount_amount' => $discountAmount,
            'taxable_amount' => $taxable,
            'cgst_amount' => $cgst,
            'sgst_amount' => $sgst,
            'igst_amount' => $igst,
            'tax_amount' => $tax,
            'line_total' => round($taxable + $tax, 2),
        ];
    }

    /**
     * Nearest-rupee round-off within ±0.99.
     */
    public function roundOff(float $amount): float
    {
        $rounded = round($amount, 0);
        $diff = round($rounded - $amount, 2);

        if (abs($diff) > 0.99) {
            return 0.0;
        }

        return $diff;
    }
}
