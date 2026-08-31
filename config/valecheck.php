<?php

return [

    'currency' => 'GBP',

    // Temporary launch flag — ValeCheck Rebuild (£14.99) is hidden from the
    // storefront while the AI damage-analysis product is finished. Flip to
    // true to bring it back with no code changes. Credit packs and
    // subscriptions are ValeCheck Plus features and are unaffected by this
    // flag — see subscriptions_enabled below.
    'rebuild_enabled' => env('REBUILD_ENABLED', true),

    // Temporary launch flag — subscriptions (Trader/Pro/Dealer) are hidden
    // until their Stripe recurring prices are set up (STRIPE_PRICE_TRADER/
    // PRO/DEALER) and there's time to test them properly. Defaults to
    // hidden so a missing env var never accidentally exposes an untested
    // feature. Credit packs are unaffected by this flag.
    'subscriptions_enabled' => env('SUBSCRIPTIONS_ENABLED', false),

    // Temporary pre-launch gate — when set, the entire site requires this
    // password (HTTP Basic Auth) before anyone can reach it, so it can't be
    // stumbled onto and used for free before payments are fully tested.
    // Empty/unset is a complete no-op — clear it in production to go fully
    // live with no code changes. See App\Http\Middleware\RequireSitePassword.
    'site_password' => env('SITE_PASSWORD'),

    'vat' => [
        'rate' => 0.20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing (VAT inclusive, in GBP)
    |--------------------------------------------------------------------------
    |
    | Three products, three different customer needs — not a basic/better/best
    | ladder:
    |   - check   (£8.99)  — history & provenance, for any used-car buyer.
    |   - plus    (£11.99) — history & provenance + market valuation, the
    |                        mass-market "is this worth the money?" product.
    |   - rebuild (£14.99) — everything in Plus + AI damage analysis, repair
    |                        cost, repaired value and maximum bid — the
    |                        specialist tool for damaged/salvage vehicles.
    */
    'pricing' => [
        'check' => [
            'label' => 'ValeCheck',
            'tagline' => 'Check the history.',
            'gross' => 8.99,
        ],
        'plus' => [
            'label' => 'ValeCheck Plus',
            'tagline' => 'Know the history. Know the value.',
            'gross' => 11.99,
        ],
        'rebuild' => [
            'label' => 'ValeCheck Rebuild',
            'tagline' => 'Know the damage. Know the numbers.',
            'gross' => 14.99,
        ],
        // A one-off top-up for an existing completed ValeCheck report,
        // reusing the same VehicleCheck row rather than starting a new one —
        // only the Plus-exclusive jobs run (valuation, salvage, tax cost),
        // never a second AutoCheck call, since that's already paid for.
        // See VehicleCheckPipeline::dispatchUpgrade().
        'plus_upgrade' => [
            'label' => 'Upgrade to ValeCheck Plus',
            'gross' => 3.50,
        ],
        // Priced off the current ValeCheck Plus gross price (admin-editable,
        // see PricingService::forCreditPack()) rather than a static amount,
        // so a pricing change here always stays in step with it.
        'credit_packs' => [
            'plus_1' => ['label' => '1 Plus Report', 'report_type' => 'plus', 'credits' => 1, 'discount' => 0],
            'plus_5' => ['label' => '5 Plus Reports', 'report_type' => 'plus', 'credits' => 5, 'discount' => 0.10],
            'plus_10' => ['label' => '10 Plus Reports', 'report_type' => 'plus', 'credits' => 10, 'discount' => 0.15],
        ],
        // Subscription allowances are keyed by report type rather than a
        // single hard-coded count, so a plan could grant a mix of report
        // types in future without a schema change. All three are flat,
        // deliberately-chosen prices, not derived from the Plus price.
        'subscriptions' => [
            'trader' => ['label' => 'Trader', 'gross' => 49.99, 'allowances' => ['plus' => 5], 'stripe_price' => env('STRIPE_PRICE_TRADER')],
            'pro' => ['label' => 'Pro', 'gross' => 94.99, 'allowances' => ['plus' => 10], 'stripe_price' => env('STRIPE_PRICE_PRO')],
            'dealer' => ['label' => 'Dealer', 'gross' => 149.99, 'allowances' => ['plus' => 30], 'stripe_price' => env('STRIPE_PRICE_DEALER')],
        ],
    ],

    // Where a submitted Enterprise contact-form enquiry (see ContactController)
    // is emailed to — no Stripe plan exists for Enterprise, it's sales-led.
    'enterprise_contact_email' => env('ENTERPRISE_CONTACT_EMAIL', 'stefan@silverbackcustomsuk.com'),

    /*
    |--------------------------------------------------------------------------
    | Salvage valuation assumptions (not guarantees — see report disclaimer)
    |--------------------------------------------------------------------------
    */
    'salvage' => [
        'cat_n_discount' => 0.25,
        'cat_s_discount' => 0.35,
    ],

    /*
    |--------------------------------------------------------------------------
    | Repair estimate assumptions
    |--------------------------------------------------------------------------
    */
    'repair_assumptions' => [
        'labour_rate_per_hour' => 55,
        'hours_by_severity' => [
            'low' => 1.5,
            'medium' => 4,
            'high' => 8,
        ],
        'part_cost_by_component' => [
            'default' => 250,
        ],
        'aftermarket_discount' => 0.35,
        'used_parts_discount' => 0.55,
        'paint_rate_per_panel' => 180,
        'materials_rate' => 0.12,
        'misc_rate' => 0.05,
        'contingency_rate' => 0.10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum bid calculation assumptions
    |--------------------------------------------------------------------------
    */
    'maximum_bid' => [
        'auction_fee_rate' => 0.06,
        'transport_cost' => 250,
        'service_mot_allowance' => 300,
        'contingency' => 500,
        'required_margin_rate' => 0.15,
        'recommended_bid_discount' => 0.08,
    ],

    'deal_score' => [
        'weights' => [
            'margin' => 40,
            'damage_severity' => 20,
            'market_confidence' => 15,
            'data_completeness' => 15,
            'ai_confidence' => 10,
        ],
        'thresholds' => [
            'buy' => 65,
            'maybe' => 40,
        ],
    ],

    'affiliate' => [
        'commissions' => [
            'check' => 1.00,
            'plus' => 1.50,
            'rebuild' => 2.00,
            'trader' => 5.00,
            'pro' => 7.50,
            'dealer' => 15.00,
        ],
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', 'anthropic'),
        'model' => env('AI_MODEL', 'claude-sonnet-4-5-20250929'),
        'max_images' => 12,
    ],

    'payment_processing' => [
        'percentage' => 0.015,
        'fixed' => 0.20,
    ],

    // One Auto API (https://docs.oneautoapi.com/) — the paid "Full Vehicle
    // Check" equivalent (Experian AutoCheck for identity+provenance, One
    // Auto's own MOT History & Tax Status), plus a valuation call,
    // SalvageGuide Salvage Check and Vehicle Tax from VRM used for
    // ValeCheck Plus. The valuation call is one of two genuinely different
    // products depending on write-off status — UK Vehicle Data for clean
    // vehicles, SalvageGuide's Bid Prediction for written-off ones (this
    // replaced Brego, which had no way to price in write-off history at
    // all) — see OneAutoMarketValuationProvider. Only ever called once a
    // report is funded (free credit, subscription allowance, or payment)
    // — never on demand. cost_per_lookup_net is per API call, not per
    // report — a Check report makes 2 calls (AutoCheck + MOT/Tax), Plus
    // makes 5 (+ valuation + Salvage Check + Vehicle Tax from VRM); see
    // ProviderLookupLog for the real count. Depends on which One Auto plan
    // tier (PrePay/Business/Enterprise/Bespoke) is active — set once
    // known, not guessed here.
    'vehicle_data' => [
        'provider' => env('VEHICLE_DATA_PROVIDER', 'mock'),
        'oneauto' => [
            'api_key' => env('ONEAUTO_API_KEY'),
            'base_url' => env('ONEAUTO_BASE_URL', 'https://api.oneautoapi.com'),
            'cost_per_lookup_net' => (float) env('ONEAUTO_COST_PER_LOOKUP_NET', 0),
            // How long a MOT History & Tax Status lookup is cached per
            // registration — shared between the free preview and the paid
            // report, so previewing then buying within this window doesn't
            // trigger a second paid call for the same data.
            'preview_cache_minutes' => (int) env('ONEAUTO_PREVIEW_CACHE_MINUTES', 30),
        ],
    ],

    // The free "is this your vehicle?" preview (make/model/colour/fuel/
    // year/MOT/tax) shown when the user clicks "Check Vehicle", before any
    // payment or credit is spent. DVLA is free but has no model field.
    // 'oneauto' reuses vehicle_data.oneauto above — no separate config,
    // since it shares the same MOT/Tax call and cache as the paid report.
    'registration_lookup' => [
        'provider' => env('REGISTRATION_LOOKUP_PROVIDER', 'mock'),
        'dvla' => [
            'api_key' => env('DVLA_API_KEY'),
            'base_url' => env('DVLA_BASE_URL', 'https://driver-vehicle-licensing.api.gov.uk'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Public listing import
    |--------------------------------------------------------------------------
    |
    | Attempts to pre-fill Rebuild listing details from a public listing URL
    | the customer pastes in, using only publicly-accessible structured
    | metadata (JSON-LD/OpenGraph) — never bypassing login, paywalls,
    | CAPTCHAs, bot detection or rate limits. If a marketplace blocks
    | automated retrieval, the import fails gracefully and manual entry
    | remains available. See app/Services/ListingImport for the provider
    | architecture and the SSRF-safe fetcher.
    */
    'listing_import' => [
        // Hidden for now — flip to true (or set LISTING_IMPORT_ENABLED)
        // to bring it back with no other code changes.
        'enabled' => env('LISTING_IMPORT_ENABLED', false),
        'cache_hours' => 6,
        'max_images' => 30,
        'max_page_size_kb' => 5120,
        'connect_timeout' => 5,
        'request_timeout' => 15,
        'max_redirects' => 5,
        'rate_limit_per_domain' => [
            'attempts' => 10,
            'decay_seconds' => 60,
        ],
        'user_agent' => env('LISTING_IMPORT_USER_AGENT', 'ValeCheckBot/1.0 (+https://valecheck.co.uk/about-our-importer)'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reports — PDF storage and retention
    |--------------------------------------------------------------------------
    |
    | Generated report PDFs are stored on a disk configured here (S3 in
    | production — local storage isn't guaranteed to persist across
    | deploys/instances). retention_days drives both the "available until"
    | date shown to customers and the reports:purge-expired command, which
    | permanently deletes report content (photos, analysis, the PDF) after
    | this many days — see app/Console/Commands/PurgeExpiredReports.php.
    */
    'reports' => [
        'pdf_disk' => env('REPORT_PDF_DISK', 's3'),
        'retention_days' => (int) env('REPORT_RETENTION_DAYS', 30),
    ],

];
