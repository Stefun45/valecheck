<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Only genuinely public, indexable pages — everything behind auth
     * (dashboard, reports, admin) is intentionally excluded, and also
     * marked noindex at the page level (see AppLayout's default). Add a
     * new entry here whenever a new public marketing/content page ships
     * (e.g. the FAQ and sample report pages).
     *
     * @var list<array{route: string, changefreq: string, priority: string}>
     */
    private const PAGES = [
        ['route' => 'welcome', 'changefreq' => 'weekly', 'priority' => '1.0'],
        ['route' => 'vehicle-checks.start', 'changefreq' => 'weekly', 'priority' => '0.9'],
        ['route' => 'sample-report', 'changefreq' => 'monthly', 'priority' => '0.7'],
        ['route' => 'faq', 'changefreq' => 'monthly', 'priority' => '0.6'],
        ['route' => 'guides.index', 'changefreq' => 'monthly', 'priority' => '0.6'],
        ['route' => 'guides.write-off-check', 'changefreq' => 'monthly', 'priority' => '0.6'],
        ['route' => 'guides.write-off-categories', 'changefreq' => 'monthly', 'priority' => '0.6'],
        ['route' => 'guides.finance-check', 'changefreq' => 'monthly', 'priority' => '0.6'],
        ['route' => 'legal.terms', 'changefreq' => 'monthly', 'priority' => '0.3'],
        ['route' => 'legal.privacy', 'changefreq' => 'monthly', 'priority' => '0.3'],
    ];

    public function index(): Response
    {
        $urls = collect(self::PAGES)->map(fn (array $page) => [
            'loc' => route($page['route']),
            'changefreq' => $page['changefreq'],
            'priority' => $page['priority'],
        ]);

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
