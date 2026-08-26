<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Creator;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CreatorController extends Controller
{
    public function index()
    {
        $creators = Creator::withCount('referrals')
            ->with('commissions')
            ->orderBy('name')
            ->get()
            ->each(function (Creator $creator) {
                $creator->pending_total = $creator->commissions
                    ->whereIn('status', [Commission::STATUS_PENDING, Commission::STATUS_APPROVED])
                    ->sum('amount');
                $creator->paid_total = $creator->commissions->where('status', Commission::STATUS_PAID)->sum('amount');
            });

        return view('admin.affiliates.index', compact('creators'));
    }

    public function create()
    {
        return view('admin.affiliates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'name' => ['required', 'string', 'max:255'],
            'referral_code' => ['nullable', 'string', 'max:50', 'unique:creators,referral_code'],
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        Creator::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'referral_code' => strtoupper(($validated['referral_code'] ?? null) ?: Str::random(8)),
        ]);

        return redirect()->route('admin.affiliates.index')->with('status', 'Affiliate created.');
    }

    public function show(Creator $creator)
    {
        $creator->load(['referrals.referredUser', 'commissions.payment', 'user']);

        return view('admin.affiliates.show', compact('creator'));
    }

    public function edit(Creator $creator)
    {
        return view('admin.affiliates.edit', compact('creator'));
    }

    public function update(Request $request, Creator $creator): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $creator->update($validated);

        return redirect()->route('admin.affiliates.index')->with('status', 'Affiliate updated.');
    }

    public function toggle(Creator $creator): RedirectResponse
    {
        $creator->update(['is_active' => ! $creator->is_active]);

        return back()->with('status', $creator->is_active ? 'Affiliate reactivated.' : 'Affiliate deactivated.');
    }
}
