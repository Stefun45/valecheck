<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminMetricReset;
use Illuminate\Http\RedirectResponse;

class AdminMetricResetController extends Controller
{
    public function resetRevenue(): RedirectResponse
    {
        AdminMetricReset::reset('revenue_today');

        return redirect()->route('admin.dashboard')->with('status', 'Revenue counter reset.');
    }
}
