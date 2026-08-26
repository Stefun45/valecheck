<?php

namespace App\Services\Reports;

use App\Models\Report;
use App\Models\VehicleCheck;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ReportPdfService
{
    /**
     * Renders and stores a report PDF, or returns the already-generated one
     * unchanged — a report's underlying data never changes after
     * completion, so there's nothing to gain from regenerating it.
     */
    public function generate(VehicleCheck $check): Report
    {
        $report = $check->report;

        if (! $report) {
            throw new RuntimeException("VehicleCheck #{$check->id} has no report to generate a PDF from.");
        }

        if ($report->hasPdf()) {
            return $report;
        }

        $view = match ($check->type) {
            VehicleCheck::TYPE_PLUS => 'pdf.plus-report',
            VehicleCheck::TYPE_REBUILD => 'pdf.rebuild-report',
            default => 'pdf.check-report',
        };

        $html = view($view, ['check' => $check])->render();

        $options = new Options;
        $options->set('isRemoteEnabled', false); // no external network calls while rendering
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4');
        $dompdf->render();

        $disk = config('valecheck.reports.pdf_disk');
        $path = "reports/{$check->id}/report.pdf";
        Storage::disk($disk)->put($path, $dompdf->output());

        $report->update([
            'pdf_disk' => $disk,
            'pdf_path' => $path,
            'pdf_generated_at' => now(),
        ]);

        return $report->fresh();
    }
}
