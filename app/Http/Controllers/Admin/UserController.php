<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditTransaction;
use App\Models\ProductPrice;
use App\Models\User;
use App\Models\UserProductPrice;
use App\Models\VehicleCheck;
use App\Services\Credits\CreditLedgerService;
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

    public function editPrices(User $user, CreditLedgerService $ledger)
    {
        $overrides = $user->productPriceOverrides()->pluck('gross', 'type');
        $standardPrices = ProductPrice::pluck('gross', 'type');

        $creditBalances = [
            VehicleCheck::TYPE_PLUS => $ledger->balance($user, VehicleCheck::TYPE_PLUS),
            VehicleCheck::TYPE_REBUILD => $ledger->balance($user, VehicleCheck::TYPE_REBUILD),
        ];

        $recentGrants = $user->creditTransactions()
            ->where('type', CreditTransaction::TYPE_FREE_GRANT)
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.users.edit-prices', compact('user', 'overrides', 'standardPrices', 'creditBalances', 'recentGrants'));
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

    public function grantCredits(Request $request, User $user, CreditLedgerService $ledger): RedirectResponse
    {
        $validated = $request->validate([
            'report_type' => ['required', 'in:'.VehicleCheck::TYPE_PLUS.','.VehicleCheck::TYPE_REBUILD],
            'amount' => ['required', 'integer', 'min:1', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $ledger->grantFreeCredits($user, $validated['report_type'], $validated['amount'], $validated['note'] ?? null);

        return redirect()->route('admin.users.edit-prices', $user)
            ->with('status', "Granted {$validated['amount']} {$validated['report_type']} credit(s) to {$user->name}.");
    }
}
