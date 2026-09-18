{{-- Google Ads base site tag - included in every layout's <head>, right
     after the charset/viewport meta as Google's own setup instructions
     specify. Renders nothing at all when GOOGLE_ADS_CONVERSION_ID isn't
     set, so local/CI/preview environments never send dev traffic into
     the real ad account. --}}
@if (config('valecheck.google_ads_id'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('valecheck.google_ads_id') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ config('valecheck.google_ads_id') }}');
    </script>
@endif
