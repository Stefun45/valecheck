<?php

namespace App\Http\Controllers;

use App\Models\VehicleCheck;
use App\Services\Reports\ReportPdfService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReportPdfController extends Controller
{
    use AuthorizesRequests;

    public function download(VehicleCheck $vehicleCheck, ReportPdfService $pdfService): RedirectResponse
    {
        $this->authorize('view', $vehicleCheck);

        if ($vehicleCheck->isPurged() || ! $vehicleCheck->report) {
            return back()->with('error', 'This report is no longer available for download.');
        }

        try {
            $report = $pdfService->generate($vehicleCheck);

            return redirect()->away($report->pdfTemporaryUrl());
        } catch (Throwable $e) {
            Log::error("Failed to generate/serve report PDF for VehicleCheck #{$vehicleCheck->id}: {$e->getMessage()}");

            return back()->with('error', "We couldn't prepare your PDF just now — please try again shortly.");
        }
    }
}
