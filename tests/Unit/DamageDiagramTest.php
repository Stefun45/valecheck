<?php

namespace Tests\Unit;

use Tests\TestCase;

class DamageDiagramTest extends TestCase
{
    private function html(array $locations): string
    {
        return view('components.damage-diagram', ['locations' => $locations])->render();
    }

    private function pinCount(string $html): int
    {
        return substr_count($html, 'fill="#DC2626"');
    }

    public function test_a_front_nearside_code_places_only_that_pin(): void
    {
        $html = $this->html(['FrontNearside']);

        $this->assertSame(1, $this->pinCount($html));
        $this->assertStringContainsString('cx="28" cy="20"', $html);
    }

    public function test_a_generic_rear_code_places_only_the_rear_pin(): void
    {
        $html = $this->html(['Rear']);

        $this->assertSame(1, $this->pinCount($html));
        $this->assertStringContainsString('cx="60" cy="190"', $html);
    }

    public function test_an_all_over_code_places_a_pin_at_every_zone(): void
    {
        $html = $this->html(['AllOver']);

        $this->assertSame(9, $this->pinCount($html));
    }

    public function test_an_unrecognised_code_is_never_silently_dropped(): void
    {
        $html = $this->html(['Interior smoke damage']);

        $this->assertStringContainsString('Also reported: Interior smoke damage', $html);
        $this->assertSame(0, $this->pinCount($html));
    }

    public function test_no_location_data_still_shows_the_diagram_greyed_out_with_a_no_data_note(): void
    {
        // Confirmed real case: a genuine Cat S record where AutoCheck's own
        // condition_data_items[0].damage_location_items came back as an
        // empty array — the diagram should still render (not disappear),
        // just visibly marked as having no location data rather than
        // implying nothing was checked.
        $html = $this->html([]);

        $this->assertStringContainsString('No damage location data provided.', $html);
        $this->assertSame(0, $this->pinCount($html));
    }
}
