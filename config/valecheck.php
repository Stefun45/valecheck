<?php

return [

    'currency' => 'GBP',

    // Temporary launch flag — ValeCheck Rebuild (£14.99), its credit packs
    // and subscription plans (Trader/Pro/Dealer, which only ever grant
    // Rebuild allowance) are hidden from the storefront while the AI
    // damage-analysis product is finished. Flip to true to bring the whole
    // thing back with no code changes.
    'rebuild_enabled' => env('REBUILD_ENABLED', true),

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
        'credit_packs' => [
            'rebuild_1' => ['label' => '1 Rebuild Report', 'gross' => 14.99, 'credits' => 1],
            'rebuild_5' => ['label' => '5 Rebuild Reports', 'gross' => 59.99, 'credits' => 5],
            'rebuild_10' => ['label' => '10 Rebuild Reports', 'gross' => 99.99, 'credits' => 10],
        ],
        // Subscription allowances are keyed by report type rather than a
        // single hard-coded count, so a plan could grant a mix of report
        // types in future without a schema change.
        'subscriptions' => [
            'trader' => ['label' => 'Trader', 'gross' => 39.99, 'allowances' => ['rebuild' => 5], 'stripe_price' => env('STRIPE_PRICE_TRADER')],
            'pro' => ['label' => 'Pro', 'gross' => 69.99, 'allowances' => ['rebuild' => 10], 'stripe_price' => env('STRIPE_PRICE_PRO')],
            'dealer' => ['label' => 'Dealer', 'gross' => 129.99, 'allowances' => ['rebuild' => null], 'stripe_price' => env('STRIPE_PRICE_DEALER')],
        ],
    ],

    'fair_use' => [
        // Soft monthly cap applied to "unlimited" Dealer plan usage.
        'dealer_monthly_soft_cap' => 40,
    ],

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

    // The expensive "Full Vehicle Check" — write-off, finance, stolen,
    // keeper, mileage history and market valuation. Only ever called once a
    // report is funded (free credit, subscription allowance, or payment) —
    // never on demand.
    'vehicle_data' => [
        'provider' => env('VEHICLE_DATA_PROVIDER', 'mock'),
        'vehiclematic' => [
            'api_key' => env('VEHICLEMATIC_API_KEY'),
            'base_url' => env('VEHICLEMATIC_BASE_URL', 'https://vehiclematic.com/products/full-vehicle-check/api/live'),
            'cost_per_lookup_net' => 2.95,
        ],
    ],

    // The cheap "is this your vehicle?" preview (make/model/colour/fuel/
    // year/MOT/tax) shown when the user clicks "Check Vehicle", before any
    // payment or credit is spent. DVLA is free but has no model field;
    // VehicleMatic's "Vehicle Details" product costs ~£0.15/lookup and does
    // include model.
    'registration_lookup' => [
        'provider' => env('REGISTRATION_LOOKUP_PROVIDER', 'mock'),
        'dvla' => [
            'api_key' => env('DVLA_API_KEY'),
            'base_url' => env('DVLA_BASE_URL', 'https://driver-vehicle-licensing.api.gov.uk'),
        ],
        'vehiclematic' => [
            'api_key' => env('VEHICLEMATIC_API_KEY'),
            'base_url' => env('VEHICLEMATIC_BASIC_BASE_URL', 'https://vehiclematic.com/products/vehicle-details/api/live'),
            'cost_per_lookup_net' => 0.15,
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
        'enabled' => env('LISTING_IMPORT_ENABLED', true),
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
