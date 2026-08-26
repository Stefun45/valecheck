<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminMetricsService;

class AdminDashboardController extends Controller
{
    public function index(AdminMetricsService $metrics)
    {
        return view('admin.dashboard', ['metrics' => $metrics->compute()]);
    }
}
