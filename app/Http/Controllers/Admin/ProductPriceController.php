<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductPrice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductPriceController extends Controller
{
    public function edit()
    {
        $prices = ProductPrice::pluck('gross', 'type');

        return view('admin.pricing.edit', compact('prices'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'check' => ['required', 'numeric', 'min:0.01'],
            'plus' => ['required', 'numeric', 'min:0.01'],
            'rebuild' => ['required', 'numeric', 'min:0.01'],
            'plus_upgrade' => ['required', 'numeric', 'min:0.01'],
        ]);

        foreach ($validated as $type => $gross) {
            ProductPrice::where('type', $type)->update(['gross' => $gross]);
        }

        return redirect()->route('admin.pricing.edit')->with('status', 'Prices updated.');
    }
}
