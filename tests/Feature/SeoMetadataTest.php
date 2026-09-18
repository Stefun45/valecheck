<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_has_a_meta_description_canonical_and_open_graph_tags(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertSee('<meta name="description" content="Instant UK vehicle history', false);
        $response->assertSee('<link rel="canonical" href="'.url('/').'"', false);
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('<meta name="twitter:card" content="summary">', false);
        $response->assertDontSee('name="robots" content="noindex', false);
    }

    public function test_the_homepage_includes_organization_and_product_structured_data(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertSee('"@type":"Organization"', false);
        $response->assertSee('"legalName":"Silverback Customs UK Ltd"', false);
        $response->assertSee('"@type":"Product"', false);
    }

    public function test_the_public_check_page_is_indexable_with_its_own_title_and_description(): void
    {
        $response = $this->get('/check')->assertOk();

        $response->assertSee('<title>Check a Vehicle — ValeCheck</title>', false);
        $response->assertSee('<meta name="description" content="Enter a UK registration', false);
        $response->assertDontSee('name="robots" content="noindex', false);
    }

    public function test_private_authenticated_pages_are_noindex_by_default(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_admin_pages_are_noindex_by_default(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_the_login_page_is_indexable(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('name="robots" content="noindex', false);
    }

    public function test_legal_pages_each_have_a_unique_meta_description(): void
    {
        $terms = $this->get(route('legal.terms'))->assertOk();
        $privacy = $this->get(route('legal.privacy'))->assertOk();

        $terms->assertSee('<meta name="description" content="ValeCheck&#039;s terms and conditions', false);
        $privacy->assertSee('<meta name="description" content="How ValeCheck collects', false);
    }

    public function test_the_sitemap_lists_every_public_page_and_excludes_private_ones(): void
    {
        $response = $this->get('/sitemap.xml')->assertOk();

        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $response->assertSee('<loc>'.url('/').'</loc>', false);
        $response->assertSee('<loc>'.route('vehicle-checks.start').'</loc>', false);
        $response->assertSee('<loc>'.route('sample-report').'</loc>', false);
        $response->assertSee('<loc>'.route('faq').'</loc>', false);
        $response->assertSee('<loc>'.route('guides.index').'</loc>', false);
        $response->assertSee('<loc>'.route('guides.write-off-check').'</loc>', false);
        $response->assertSee('<loc>'.route('legal.terms').'</loc>', false);
        $response->assertSee('<loc>'.route('legal.privacy').'</loc>', false);
        $response->assertDontSee('dashboard', false);
    }

    public function test_the_faq_page_has_a_meta_description_and_faqpage_structured_data(): void
    {
        $response = $this->get(route('faq'))->assertOk();

        $response->assertSee('<meta name="description" content="Answers to common questions', false);
        $response->assertSee('"@type":"FAQPage"', false);
        $response->assertSeeText('What is ValeCheck?');
        $response->assertSeeText('What do the different write-off categories mean?');
        $response->assertSeeText('Category A: scrap only');
        $response->assertDontSee('name="robots" content="noindex', false);
    }

    public function test_each_guide_page_is_indexable_with_its_own_title_and_links_to_the_free_check(): void
    {
        $writeOffCheck = $this->get(route('guides.write-off-check'))->assertOk();
        $categories = $this->get(route('guides.write-off-categories'))->assertOk();
        $financeCheck = $this->get(route('guides.finance-check'))->assertOk();

        $writeOffCheck->assertSee('<title>How to Check If a Car Is Written Off (UK)', false);
        $categories->assertSee('<title>Car Write-Off Categories Explained', false);
        $financeCheck->assertSee('<title>How to Check a Car for Outstanding Finance (UK)', false);

        foreach ([$writeOffCheck, $categories, $financeCheck] as $response) {
            $response->assertDontSee('name="robots" content="noindex', false);
            $response->assertSee(route('vehicle-checks.start'), false);
        }

        // Cross-linked to each other, not just standalone pages.
        $writeOffCheck->assertSee(route('guides.write-off-categories'), false);
        $categories->assertSee(route('guides.write-off-check'), false);
    }

    public function test_the_guides_index_lists_every_guide(): void
    {
        $response = $this->get(route('guides.index'))->assertOk();

        $response->assertDontSee('name="robots" content="noindex', false);
        $response->assertSee(route('guides.write-off-check'), false);
        $response->assertSee(route('guides.write-off-categories'), false);
        $response->assertSee(route('guides.finance-check'), false);
    }

    public function test_the_google_ads_tag_is_absent_by_default_so_dev_traffic_never_reaches_the_real_account(): void
    {
        config(['valecheck.google_ads_id' => null]);

        $this->get('/')->assertOk()->assertDontSee('googletagmanager.com/gtag/js', false);
    }

    public function test_the_google_ads_tag_renders_on_public_pages_once_configured(): void
    {
        config(['valecheck.google_ads_id' => 'AW-625010447']);

        $this->get('/')->assertOk()->assertSee('googletagmanager.com/gtag/js?id=AW-625010447', false);
        $this->get(route('vehicle-checks.start'))->assertOk()->assertSee('googletagmanager.com/gtag/js?id=AW-625010447', false);
        $this->get(route('faq'))->assertOk()->assertSee('googletagmanager.com/gtag/js?id=AW-625010447', false);
    }

    public function test_robots_txt_points_to_the_sitemap(): void
    {
        $contents = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap: https://valecheck.com/sitemap.xml', $contents);
    }
}
