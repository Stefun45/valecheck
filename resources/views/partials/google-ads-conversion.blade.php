{{--
    Fires the Google Ads "Purchase" conversion with the real amount paid,
    reading the payment id StripeCheckoutService puts on its own success_url
    (?paid=1&payment=<id>) rather than guessing which payment just
    completed - works the same way for a fresh check, an upgrade, or a
    credit pack, since each already creates its own Payment row before
    redirecting to Stripe.

    Deliberately re-checks the Payment's own status is genuinely "paid"
    rather than trusting the query string alone (the ?paid=1 redirect from
    Stripe can arrive before the webhook that actually marks it paid) -
    on show-check, this partial re-renders on every wire:poll tick while
    the report is still processing, so it naturally starts firing the
    moment the webhook lands rather than needing a page reload.

    The sessionStorage guard stops it firing more than once per browser
    tab even though the underlying script tag itself can be reinserted
    into the DOM multiple times by that same polling. Google's own
    transaction_id-based deduplication is the second line of defence for
    every other case (a different tab, a cleared session, a bookmark).
--}}
@php
    $googleAdsConversionPayment = null;

    if (request()->query('paid') && request()->query('payment') && config('valecheck.google_ads_id') && config('valecheck.google_ads_purchase_label')) {
        $googleAdsConversionPayment = \App\Models\Payment::find(request()->query('payment'));
    }
@endphp
@if ($googleAdsConversionPayment && $googleAdsConversionPayment->status === \App\Models\Payment::STATUS_PAID)
    <script>
        (function () {
            var key = 'valecheck_ga_conversion_{{ $googleAdsConversionPayment->id }}';

            try {
                if (sessionStorage.getItem(key)) {
                    return;
                }
                sessionStorage.setItem(key, '1');
            } catch (e) {
                // Storage unavailable (private browsing, blocked) - fall
                // through and fire anyway; transaction_id-based
                // deduplication on Google's side is the fallback here.
            }

            gtag('event', 'conversion', {
                'send_to': '{{ config('valecheck.google_ads_id') }}/{{ config('valecheck.google_ads_purchase_label') }}',
                'value': {{ (float) $googleAdsConversionPayment->gross }},
                'currency': '{{ $googleAdsConversionPayment->currency }}',
                'transaction_id': '{{ $googleAdsConversionPayment->id }}',
            });
        })();
    </script>
@endif
