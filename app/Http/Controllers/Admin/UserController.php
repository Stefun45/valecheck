<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductPrice;
use App\Models\User;
use App\Models\UserProductPrice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->when($search !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
            ))
            ->withCount('vehicleChecks')
            ->with('productPriceOverrides')
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'search' => $search]);
    }

    public function editPrices(User $user)
    {
        $overrides = $user->productPriceOverrides()->pluck('gross', 'type');
        $standardPrices = ProductPrice::pluck('gross', 'type');

        return view('admin.users.edit-prices', compact('user', 'overrides', 'standardPrices'));
    }

    public function updatePrices(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            // 0 is allowed deliberately — a £0 account price skips Stripe
            // Checkout entirely (see VehicleCheckOrderService), which
            // doesn't support a genuine £0 charge anyway.
            'check' => ['nullable', 'numeric', 'min:0'],
            'plus' => ['nullable', 'numeric', 'min:0'],
            'rebuild' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($validated as $type => $gross) {
            if ($gross === null || $gross === '') {
                UserProductPrice::where('user_id', $user->id)->where('type', $type)->delete();

                continue;
            }

            UserProductPrice::updateOrCreate(['user_id' => $user->id, 'type' => $type], ['gross' => $gross]);
        }

        return redirect()->route('admin.users.index')->with('status', "Custom prices updated for {$user->name}.");
    }
}
