<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductPrice;
use App\Models\SitePromotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

class SitePromotionController extends Controller
{
    /** @var list<string> */
    private const TYPES = ['check', 'plus', 'rebuild', 'plus_upgrade'];

    public function edit()
    {
        $standardPrices = ProductPrice::pluck('gross', 'type');
        $promotions = collect(self::TYPES)->mapWithKeys(fn (string $type) => [$type => SitePromotion::current($type)]);

        return view('admin.promotion.edit', compact('standardPrices', 'promotions'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'promotions' => ['required', 'array'],
            'promotions.*.is_active' => ['boolean'],
            'promotions.*.discounted_gross' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $validator->after(fn (ValidatorInstance $validator) => $this->rejectDiscountsThatAreNotActuallyLower($validator));

        $validated = $validator->validate();

        foreach (self::TYPES as $type) {
            $row = $validated['promotions'][$type] ?? [];

            SitePromotion::current($type)->update([
                'is_active' => ! empty($row['is_active']),
                'discounted_gross' => $row['discounted_gross'] ?? null,
            ]);
        }

        return redirect()->route('admin.promotion.edit')->with('status', 'Promotion updated.');
    }

    /**
     * A "discounted" price that isn't actually lower than the current
     * standard price would show as a discount everywhere while quietly
     * charging the same or more - reject that outright rather than let it
     * through and mislead a customer.
     */
    private function rejectDiscountsThatAreNotActuallyLower(ValidatorInstance $validator): void
    {
        $data = $validator->getData();

        foreach (self::TYPES as $type) {
            $row = $data['promotions'][$type] ?? [];
            $discountedGross = $row['discounted_gross'] ?? null;

            if ($discountedGross === null || $discountedGross === '') {
                continue;
            }

            $standardGross = (float) (ProductPrice::where('type', $type)->value('gross') ?? config("valecheck.pricing.{$type}.gross"));

            if ((float) $discountedGross >= $standardGross) {
                $validator->errors()->add(
                    "promotions.{$type}.discounted_gross",
                    'The discounted price must be lower than the standard price (£'.number_format($standardGross, 2).').'
                );
            }
        }
    }
}
