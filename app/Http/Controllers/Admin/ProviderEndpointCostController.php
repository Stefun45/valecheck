<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderEndpointCost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProviderEndpointCostController extends Controller
{
    public function edit()
    {
        $costs = ProviderEndpointCost::pluck('cost_net', 'endpoint');

        return view('admin.provider-costs.edit', compact('costs'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            collect(ProviderEndpointCost::ENDPOINTS)
                ->keys()
                ->mapWithKeys(fn (string $endpoint) => [ProviderEndpointCost::fieldName($endpoint) => ['required', 'numeric', 'min:0']])
                ->all()
        );

        foreach (ProviderEndpointCost::ENDPOINTS as $endpoint => $label) {
            ProviderEndpointCost::updateOrCreate(
                ['endpoint' => $endpoint],
                ['cost_net' => $validated[ProviderEndpointCost::fieldName($endpoint)]],
            );
        }

        return redirect()->route('admin.provider-costs.edit')->with('status', 'Provider costs updated.');
    }
}
