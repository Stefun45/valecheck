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
        return substr_count($html, 'data-zone="');
    }

    public function test_a_front_nearside_code_shows_the_front_view_with_that_pin(): void
    {
        $html = $this->html(['FrontNearside']);

        $this->assertStringContainsString('data-view="front"', $html);
        $this->assertSame(1, $this->pinCount($html));
        $this->assertStringContainsString('data-zone="front-nearside"', $html);
        $this->assertStringContainsString('front.svg', $html);
    }

    public function test_a_generic_rear_code_shows_the_rear_view_with_only_the_rear_pin(): void
    {
        $html = $this->html(['Rear']);

        $this->assertStringContainsString('data-view="rear"', $html);
        $this->assertSame(1, $this->pinCount($html));
        $this->assertStringContainsString('data-zone="rear"', $html);
        $this->assertStringContainsString('rear.svg', $html);
    }

    public function test_nearside_and_offside_together_default_to_the_unmirrored_side_view(): void
    {
        // Only one profile image exists, so both sides can't be shown
        // correctly mirrored at once — falls back to the unmirrored
        // (nearside) image rather than guess.
        $html = $this->html(['Nearside', 'Offside']);

        $this->assertStringContainsString('data-view="side"', $html);
        $this->assertStringNotContainsString('scaleX(-1)', $html);
        $this->assertStringContainsString('data-zone="nearside"', $html);
        $this->assertStringContainsString('data-zone="offside"', $html);
    }

    public function test_offside_alone_shows_the_side_view_mirrored(): void
    {
        $html = $this->html(['Offside']);

        $this->assertStringContainsString('data-view="side"', $html);
        $this->assertStringContainsString('scaleX(-1)', $html);
        $this->assertSame(1, $this->pinCount($html));
    }

    public function test_an_all_over_code_shows_a_generic_view_with_no_specific_pins(): void
    {
        // No single angle can show damage "all over" the vehicle, so
        // rather than fabricate nine precise pins on one photo, this
        // falls back to a plain illustrative shot with no pins — the
        // "Damage area: ..." text elsewhere on the report still states
        // the real value in full.
        $html = $this->html(['AllOver']);

        $this->assertStringContainsString('data-view="generic"', $html);
        $this->assertSame(0, $this->pinCount($html));
    }

    public function test_an_unrecognised_code_is_never_silently_dropped(): void
    {
        $html = $this->html(['Interior smoke damage']);

        $this->assertStringContainsString('Also reported: Interior smoke damage', $html);
        $this->assertSame(0, $this->pinCount($html));
    }

    public function test_a_recognised_zone_not_covered_by_the_shown_view_is_still_listed(): void
    {
        // Front and rear damage can't both be pinned on one image —
        // the front view is shown (front takes priority), and the
        // rear zone is still named in the note underneath rather than
        // silently disappearing.
        $html = $this->html(['FrontNearside', 'Rear']);

        $this->assertStringContainsString('data-view="front"', $html);
        $this->assertSame(1, $this->pinCount($html));
        $this->assertStringContainsString('Also reported: Rear', $html);
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
