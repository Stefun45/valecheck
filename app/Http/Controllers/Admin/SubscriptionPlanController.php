<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Plans live in the database specifically so a price change or a new
 * plan never needs a deploy - see SubscriptionPlan's own docblock.
 */
class SubscriptionPlanController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::orderBy('sort_order')->get();

        return view('admin.subscription-plans.index', compact('plans'));
    }

    public function store(Request $request): RedirectResponse
    {
        SubscriptionPlan::create($this->validated($request));

        return redirect()->route('admin.subscription-plans.index')->with('status', 'Plan created.');
    }

    public function update(Request $request, SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $subscriptionPlan->update($this->validated($request));

        return redirect()->route('admin.subscription-plans.index')->with('status', 'Plan updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'group' => ['required', 'string', 'in:'.SubscriptionPlan::GROUP_PRO.','.SubscriptionPlan::GROUP_DEALER],
            'stripe_price_id' => ['nullable', 'string', 'max:255'],
            'monthly_net' => ['required', 'numeric', 'min:0.01'],
            'monthly_credits' => ['required', 'integer', 'min:1'],
            'additional_credit_net' => ['required', 'numeric', 'min:0.01'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        // A checkbox simply isn't present in the request when unchecked,
        // so this must be resolved explicitly rather than left to the
        // validated array, or unchecking it would silently do nothing.
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
