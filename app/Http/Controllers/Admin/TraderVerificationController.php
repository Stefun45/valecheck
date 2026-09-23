<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TraderVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TraderVerificationController extends Controller
{
    public function index()
    {
        // Pending first - that's the queue an admin actually needs to work
        // through; already-decided rows are there for reference only.
        $verifications = TraderVerification::with('user')
            ->orderByRaw("status = 'pending' desc")
            ->latest('submitted_at')
            ->get();

        return view('admin.trader-verifications.index', compact('verifications'));
    }

    public function approve(Request $request, TraderVerification $traderVerification): RedirectResponse
    {
        $traderVerification->update([
            'status' => TraderVerification::STATUS_APPROVED,
            'notes' => $request->input('notes'),
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Trader verification approved.');
    }

    public function reject(Request $request, TraderVerification $traderVerification): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        $traderVerification->update([
            'status' => TraderVerification::STATUS_REJECTED,
            'notes' => $validated['notes'],
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Trader verification rejected.');
    }
}
