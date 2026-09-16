<?php

namespace App\Http\Controllers;

use App\Models\VehicleCheck;

class SampleReportController extends Controller
{
    public function show()
    {
        $vehicleCheck = VehicleCheck::where('is_sample', true)->latest()->firstOrFail();

        // The sample is copied from a real vehicle's real history — masking
        // its actual plate here (in memory only, never saved) means this
        // permanently public, indexed page never identifies a specific
        // real vehicle, even though the underlying data was genuine.
        $vehicleCheck->registration = 'AB12 SAM';

        return view('sample-report', ['vehicleCheck' => $vehicleCheck]);
    }
}
