<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookieConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_consent_mode_defaults_every_category_to_denied_before_the_config_call(): void
    {
        config(['valecheck.google_ads_id' => 'AW-625010447']);

        $html = $this->get('/')->assertOk()->getContent();

        $consentPos = strpos($html, "gtag('consent', 'default'");
        $deniedPos = strpos($html, "'ad_storage': 'denied'");
        $configPos = strpos($html, "gtag('config', 'AW-625010447')");

        $this->assertNotFalse($consentPos, 'Consent Mode default call is missing.');
        $this->assertNotFalse($configPos, 'gtag config call is missing.');
        $this->assertTrue($consentPos < $configPos, 'Consent default must be set before the config call.');
        $this->assertTrue($deniedPos !== false && $deniedPos > $consentPos && $deniedPos < $configPos);
    }

    public function test_the_cookie_banner_appears_when_google_ads_is_configured(): void
    {
        config(['valecheck.google_ads_id' => 'AW-625010447']);

        $this->get('/')->assertOk()->assertSee('valecheck_cookie_consent', false);
    }

    public function test_the_cookie_banner_is_absent_when_google_ads_is_not_configured(): void
    {
        config(['valecheck.google_ads_id' => null]);

        $this->get('/')->assertOk()->assertDontSee('valecheck_cookie_consent', false);
    }

    public function test_the_privacy_policy_accurately_describes_the_advertising_cookie(): void
    {
        // A real production bug this replaces: the page used to state
        // outright "We don't use advertising or tracking cookies", which
        // became false the moment Google Ads tracking shipped.
        $response = $this->get(route('legal.privacy'))->assertOk();

        $response->assertSeeText('Google Ads advertising');
        $response->assertDontSeeText("We don't use advertising or tracking cookies");
    }
}
