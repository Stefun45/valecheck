<?php

namespace App\Services\Valuation;

use App\DataTransferObjects\SalvageValuation;

/**
 * Cat N/S discount percentages are configurable ASSUMPTIONS, not
 * guarantees — actual value depends on make/model, age, mileage,
 * specification, desirability, original damage, repair quality,
 * documentation, market conditions and buyer perception.
 */
class SalvageValuationService
{
    public function valuate(float $cleanValue, ?string $writeOffCategory): SalvageValuation
    {
        return match ($writeOffCategory) {
            'N' => $this->applyDiscount($cleanValue, 'N', (float) config('valecheck.salvage.cat_n_discount')),
            'S' => $this->applyDiscount($cleanValue, 'S', (float) config('valecheck.salvage.cat_s_discount')),
            'A', 'B' => new SalvageValuation(
                cleanValue: $cleanValue,
                category: $writeOffCategory,
                discountApplied: null,
                adjustedValue: null,
                note: "Category {$writeOffCategory} vehicles are not normally roadworthy or resaleable as a repaired car — this assessment does not attempt a repaired-value estimate.",
            ),
            default => new SalvageValuation(
                cleanValue: $cleanValue,
                category: null,
                discountApplied: 0.0,
                adjustedValue: $cleanValue,
                note: 'No write-off history recorded — clean market value used with no salvage adjustment.',
            ),
        };
    }

    private function applyDiscount(float $cleanValue, string $category, float $discount): SalvageValuation
    {
        return new SalvageValuation(
            cleanValue: $cleanValue,
            category: $category,
            discountApplied: $discount,
            adjustedValue: round($cleanValue * (1 - $discount), 2),
            note: "Category {$category} assumption: repaired value estimated at ".round((1 - $discount) * 100)
                .'% of clean market value. Actual value depends on make/model, age, mileage, specification, '
                .'desirability, original damage, repair quality, documentation, market conditions and buyer perception.',
        );
    }
}
