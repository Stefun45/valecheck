{{-- Google Ads base site tag - included in every layout's <head>, right
     after the charset/viewport meta as Google's own setup instructions
     specify. Renders nothing at all when GOOGLE_ADS_CONVERSION_ID isn't
     set, so local/CI/preview environments never send dev traffic into
     the real ad account. --}}
@if (config('valecheck.google_ads_id'))
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}

        {{-- Google's Consent Mode: every category defaults to denied on
             every page load - no cookie is written and no ad data is sent
             until the visitor actually accepts (see
             cookie-consent-banner.blade.php, which re-grants this
             instantly on later page loads for a visitor who already
             accepted, and is the only other place these strings appear). --}}
        gtag('consent', 'default', {
            'ad_storage': 'denied',
            'ad_user_data': 'denied',
            'ad_personalization': 'denied',
            'analytics_storage': 'denied',
        });

        gtag('js', new Date());
        gtag('config', '{{ config('valecheck.google_ads_id') }}');
    </script>
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('valecheck.google_ads_id') }}"></script>
@endif
