<?php

namespace App\Http\Controllers;

use App\Models\TraderVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TraderVerificationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless(config('valecheck.subscriptions_enabled'), 404);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_number' => ['required', 'string', 'max:50'],
            'vat_number' => ['nullable', 'string', 'max:50'],
        ]);

        // updateOrCreate, not create - a resubmission after rejection reuses
        // the same row (the unique user_id constraint would reject a second
        // one anyway), reopening it as pending and clearing any previous
        // admin decision rather than leaving stale approved/rejected state.
        TraderVerification::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                ...$validated,
                'status' => TraderVerification::STATUS_PENDING,
                'submitted_at' => now(),
                'reviewed_at' => null,
                'reviewed_by' => null,
                'notes' => null,
            ]
        );

        return redirect()->route('dashboard')->with('status', 'Your business details have been submitted for review.');
    }
}
