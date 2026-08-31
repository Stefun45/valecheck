<?php

namespace Tests\Unit;

use Dompdf\Dompdf;
use Dompdf\Options;
use Tests\TestCase;

class ReportVerdictBadgeTest extends TestCase
{
    /**
     * Same reasoning as PdfStatusTickTest — this component is used in both
     * the web report and the PDF cover page, built entirely from plain CSS
     * shapes specifically so it renders in dompdf. Confirm each of the
     * three tones actually draws something rather than silently nothing.
     */
    private function renderToPdfBytes(string $bodyHtml): string
    {
        $dompdf = new Dompdf(new Options);
        $dompdf->loadHtml("<html><body>{$bodyHtml}</body></html>");
        $dompdf->setPaper('a4');
        $dompdf->render();

        return $dompdf->output();
    }

    public function test_every_tone_renders_meaningfully_more_than_a_blank_page(): void
    {
        $blankSize = strlen($this->renderToPdfBytes(''));

        foreach (['good', 'warning', 'unavailable'] as $tone) {
            $html = view('components.report-verdict-badge', ['tone' => $tone, 'label' => ucfirst($tone), 'size' => 72])->render();
            $size = strlen($this->renderToPdfBytes($html));

            $this->assertGreaterThan($blankSize + 500, $size, "Tone [{$tone}] did not render meaningful content.");
        }
    }

    public function test_the_label_text_is_shown(): void
    {
        $html = view('components.report-verdict-badge', ['tone' => 'good', 'label' => 'Clean History', 'size' => 72])->render();

        $this->assertStringContainsString('Clean History', $html);
    }
}
