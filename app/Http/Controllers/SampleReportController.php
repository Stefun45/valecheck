<?php

namespace App\Http\Controllers;

use App\Models\VehicleCheck;

class SampleReportController extends Controller
{
    public function show()
    {
        $vehicleCheck = VehicleCheck::where('is_sample', true)->latest()->firstOrFail();

        return view('sample-report', ['vehicleCheck' => $vehicleCheck]);
    }
}
