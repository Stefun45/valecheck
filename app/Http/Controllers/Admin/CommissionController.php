<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use Illuminate\Http\RedirectResponse;

class CommissionController extends Controller
{
    public function markPaid(Commission $commission): RedirectResponse
    {
        if ($commission->status !== Commission::STATUS_REVERSED) {
            $commission->update(['status' => Commission::STATUS_PAID]);
        }

        return back()->with('status', 'Commission marked as paid.');
    }
}
