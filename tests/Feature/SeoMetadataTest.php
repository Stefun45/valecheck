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
        $response->assertDontSee('name="robots" content="noindex', false);
    }

    public function test_robots_txt_points_to_the_sitemap(): void
    {
        $contents = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap: https://valecheck.com/sitemap.xml', $contents);
    }
}
