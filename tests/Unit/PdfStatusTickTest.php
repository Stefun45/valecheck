<?php

namespace Tests\Unit;

use Dompdf\Dompdf;
use Dompdf\Options;
use Tests\TestCase;

class PdfStatusTickTest extends TestCase
{
    /**
     * dompdf does not reliably render inline SVG — confirmed empirically:
     * the original <x-status-tick> SVG component produced a PDF content
     * stream byte-identical to a completely blank page, despite rendering
     * correctly on the web (browser-rendered SVG). No exception is thrown
     * and a pdf_path is still written, so nothing else catches this class
     * of bug — a string/HTML assertion can't either, since the HTML going
     * into dompdf is correct; it's dompdf's own rendering that silently
     * drops it. Comparing rendered byte size against a blank-page control
     * is the only reliable signal that something was actually drawn.
     */
    private function renderToPdfBytes(string $bodyHtml): string
    {
        $dompdf = new Dompdf(new Options);
        $dompdf->loadHtml("<html><body>{$bodyHtml}</body></html>");
        $dompdf->setPaper('a4');
        $dompdf->render();

        return $dompdf->output();
    }

    public function test_an_ok_tick_renders_meaningfully_more_than_a_blank_page(): void
    {
        $blankSize = strlen($this->renderToPdfBytes(''));
        $tickSize = strlen($this->renderToPdfBytes(view('components.pdf-status-tick', ['ok' => true, 'size' => 24])->render()));

        $this->assertGreaterThan($blankSize + 500, $tickSize);
    }

    public function test_a_not_ok_cross_renders_meaningfully_more_than_a_blank_page(): void
    {
        $blankSize = strlen($this->renderToPdfBytes(''));
        $crossSize = strlen($this->renderToPdfBytes(view('components.pdf-status-tick', ['ok' => false, 'size' => 24])->render()));

        $this->assertGreaterThan($blankSize + 500, $crossSize);
    }

    public function test_the_original_svg_tick_component_is_confirmed_broken_in_dompdf(): void
    {
        // Documents *why* pdf-status-tick.blade.php exists as a separate
        // component from the web report's status-tick.blade.php, so a
        // future "these look like duplicates, let's merge them" cleanup
        // doesn't silently reintroduce the blank-PDF bug.
        $blankSize = strlen($this->renderToPdfBytes(''));
        $svgTickSize = strlen($this->renderToPdfBytes(view('components.status-tick', ['ok' => true, 'size' => 24])->render()));

        $this->assertSame($blankSize, $svgTickSize);
    }
}
