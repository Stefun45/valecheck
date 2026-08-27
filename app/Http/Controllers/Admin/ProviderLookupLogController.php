<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderLookupLog;

class ProviderLookupLogController extends Controller
{
    public function index()
    {
        $logs = ProviderLookupLog::with('vehicleCheck')
            ->latest('id')
            ->paginate(50);

        return view('admin.provider-lookups.index', ['logs' => $logs]);
    }
}
