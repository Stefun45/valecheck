<?php

namespace App\Http\Controllers;

use App\Models\VehicleCheck;
use App\Services\Reports\ReportPdfService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class ReportPdfController extends Controller
{
    use AuthorizesRequests;

    public function download(VehicleCheck $vehicleCheck, ReportPdfService $pdfService): RedirectResponse
    {
        $this->authorize('view', $vehicleCheck);

        if ($vehicleCheck->isPurged() || ! $vehicleCheck->report) {
            return back()->with('error', 'This report is no longer available for download.');
        }

        $report = $pdfService->generate($vehicleCheck);

        return redirect()->away($report->pdfTemporaryUrl());
    }
}
