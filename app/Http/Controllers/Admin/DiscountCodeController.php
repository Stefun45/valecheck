<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DiscountCodeController extends Controller
{
    public function index()
    {
        $discountCodes = DiscountCode::latest()->get();

        return view('admin.discount-codes.index', compact('discountCodes'));
    }

    public function create()
    {
        return view('admin.discount-codes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'unique:discount_codes,code'],
            'type' => ['required', Rule::in([DiscountCode::TYPE_PERCENTAGE, DiscountCode::TYPE_FIXED])],
            'value' => ['required', 'numeric', 'min:0.01', $this->percentageCeilingRule($request)],
            'applicable_products' => ['nullable', 'array'],
            'applicable_products.*' => ['in:check,plus,rebuild'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ]);

        DiscountCode::create([
            'code' => strtoupper(($validated['code'] ?? null) ?: Str::random(8)),
            'type' => $validated['type'],
            'value' => $validated['value'],
            'applicable_products' => $validated['applicable_products'] ?? null,
            'max_redemptions' => $validated['max_redemptions'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return redirect()->route('admin.discount-codes.index')->with('status', 'Discount code created.');
    }

    public function edit(DiscountCode $discountCode)
    {
        return view('admin.discount-codes.edit', compact('discountCode'));
    }

    public function update(Request $request, DiscountCode $discountCode): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in([DiscountCode::TYPE_PERCENTAGE, DiscountCode::TYPE_FIXED])],
            'value' => ['required', 'numeric', 'min:0.01', $this->percentageCeilingRule($request)],
            'applicable_products' => ['nullable', 'array'],
            'applicable_products.*' => ['in:check,plus,rebuild'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $discountCode->update([
            'type' => $validated['type'],
            'value' => $validated['value'],
            'applicable_products' => $validated['applicable_products'] ?? null,
            'max_redemptions' => $validated['max_redemptions'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return redirect()->route('admin.discount-codes.index')->with('status', 'Discount code updated.');
    }

    public function toggle(DiscountCode $discountCode): RedirectResponse
    {
        $discountCode->update(['is_active' => ! $discountCode->is_active]);

        return back()->with('status', $discountCode->is_active ? 'Code reactivated.' : 'Code deactivated.');
    }

    private function percentageCeilingRule(Request $request): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($request) {
            if ($request->input('type') === DiscountCode::TYPE_PERCENTAGE && (float) $value > 100) {
                $fail('A percentage discount cannot exceed 100.');
            }
        };
    }
}
