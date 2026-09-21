<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SitePromotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SitePromotionController extends Controller
{
    public function edit()
    {
        $promotion = SitePromotion::current();

        return view('admin.promotion.edit', compact('promotion'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['boolean'],
            // Capped below 100 so a fat-fingered entry can't accidentally
            // make every report genuinely free (gross=0 routes to the
            // free-funding path - see VehicleCheckOrderService) without a
            // deliberate, explicit choice to do that.
            'percentage' => ['required', 'numeric', 'min:0', 'max:95'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        SitePromotion::current()->update([
            'is_active' => $request->boolean('is_active'),
            'percentage' => $validated['percentage'],
            'label' => $validated['label'] ?: 'Launch Offer',
        ]);

        return redirect()->route('admin.promotion.edit')->with('status', 'Promotion updated.');
    }
}
