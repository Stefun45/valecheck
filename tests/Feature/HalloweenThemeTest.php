<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HalloweenThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_the_homepage_shows_the_halloween_theme_during_the_configured_window(): void
    {
        Carbon::setTestNow('2026-10-31 12:00:00');

        $this->get('/')
            ->assertOk()
            ->assertSee('🎃', false)
            ->assertSee('vale-hero-wash--halloween', false);
    }

    public function test_the_homepage_reverts_to_the_normal_theme_once_the_window_has_passed(): void
    {
        Carbon::setTestNow('2026-11-01 00:00:01');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('🎃', false)
            ->assertDontSee('vale-hero-wash--halloween', false);
    }

    public function test_the_theme_has_not_started_yet_before_the_configured_window(): void
    {
        Carbon::setTestNow('2026-10-29 23:59:59');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('🎃', false);
    }
}
