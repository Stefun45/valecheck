<?php

namespace Tests\Unit;

use Dompdf\Dompdf;
use Dompdf\Options;
use Tests\TestCase;

class DamageDiagramTest extends TestCase
{
    private function html(array $locations): string
    {
        return view('components.damage-diagram', ['locations' => $locations])->render();
    }

    public function test_a_front_nearside_code_highlights_only_that_cell(): void
    {
        $html = $this->html(['FrontNearside']);

        $this->assertMatchesRegularExpression('/#DC2626[^>]*>\s*F\/NS/', $html);
        $this->assertDoesNotMatchRegularExpression('/#DC2626[^>]*>\s*REAR/', $html);
        $this->assertDoesNotMatchRegularExpression('/#DC2626[^>]*>\s*ROOF/', $html);
    }

    public function test_a_generic_rear_code_highlights_the_rear_cell_only(): void
    {
        $html = $this->html(['Rear']);

        $this->assertMatchesRegularExpression('/#DC2626[^>]*>\s*REAR/', $html);
        $this->assertDoesNotMatchRegularExpression('/#DC2626[^>]*>\s*R\/NS/', $html);
        $this->assertDoesNotMatchRegularExpression('/#DC2626[^>]*>\s*R\/OS/', $html);
    }

    public function test_an_all_over_code_highlights_every_cell(): void
    {
        $html = $this->html(['AllOver']);

        $this->assertSame(9, substr_count($html, 'background:#DC2626'));
    }

    public function test_an_unrecognised_code_is_never_silently_dropped(): void
    {
        $html = $this->html(['Interior smoke damage']);

        $this->assertStringContainsString('Also reported: Interior smoke damage', $html);
        // Nothing on the grid is highlighted for a code we can't place.
        $this->assertSame(0, substr_count($html, 'background:#DC2626'));
    }

    public function test_no_location_data_still_shows_the_diagram_greyed_out_with_a_no_data_banner(): void
    {
        // Confirmed real case: a genuine Cat S record where AutoCheck's own
        // condition_data_items[0].damage_location_items came back as an
        // empty array — the diagram should still render (not disappear),
        // just visibly marked as having no location data rather than
        // implying nothing was checked.
        $html = $this->html([]);

        $this->assertStringContainsString('NO DATA PROVIDED', $html);
        $this->assertSame(0, substr_count($html, 'background:#DC2626'));
    }

    public function test_it_renders_meaningfully_more_than_a_blank_page_in_dompdf(): void
    {
        $dompdf = new Dompdf(new Options);
        $dompdf->loadHtml('<html><body></body></html>');
        $dompdf->setPaper('a4');
        $dompdf->render();
        $blankSize = strlen($dompdf->output());

        $dompdf = new Dompdf(new Options);
        $dompdf->loadHtml('<html><body>'.$this->html(['FrontNearside']).'</body></html>');
        $dompdf->setPaper('a4');
        $dompdf->render();
        $diagramSize = strlen($dompdf->output());

        $this->assertGreaterThan($blankSize + 500, $diagramSize);
    }
}
