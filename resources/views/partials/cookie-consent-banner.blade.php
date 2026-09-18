{{-- Paired with google-ads-tag.blade.php's Consent Mode default (denied).
     Only rendered when Google Ads tracking is actually configured -
     nothing to ask consent for otherwise. The strings here
     ('ad_storage' etc.) must stay in sync with the default set there. --}}
@if (config('valecheck.google_ads_id'))
    <div
        x-data="{
            visible: false,
            storageKey: 'valecheck_cookie_consent',
            init() {
                let stored = null;
                try { stored = localStorage.getItem(this.storageKey); } catch (e) {}

                if (stored === 'granted') {
                    this.grant(false);
                } else if (stored !== 'denied') {
                    this.visible = true;
                }
            },
            grant(remember = true) {
                gtag('consent', 'update', {
                    'ad_storage': 'granted',
                    'ad_user_data': 'granted',
                    'ad_personalization': 'granted',
                    'analytics_storage': 'granted',
                });
                if (remember) {
                    try { localStorage.setItem(this.storageKey, 'granted'); } catch (e) {}
                }
                this.visible = false;
            },
            deny() {
                try { localStorage.setItem(this.storageKey, 'denied'); } catch (e) {}
                this.visible = false;
            },
        }"
        x-show="visible"
        x-cloak
        style="display: none;"
        class="fixed inset-x-0 bottom-0 z-40 bg-vale-navy text-white"
    >
        <div class="max-w-4xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-6">
            <p class="text-sm text-white/80 flex-1">
                We use essential cookies to run the site, and (only with your consent) advertising cookies to measure
                how well our ads perform. See our
                <a href="{{ route('legal.privacy') }}" class="underline hover:text-white">Privacy Policy</a> for details.
            </p>
            <div class="flex gap-3 shrink-0">
                <button type="button" x-on:click="deny()" class="px-4 py-2 rounded-full border border-white/30 text-sm font-semibold hover:bg-white/10 transition">
                    Reject
                </button>
                <button type="button" x-on:click="grant()" class="px-4 py-2 rounded-full bg-vale-red text-sm font-semibold hover:bg-red-600 transition">
                    Accept
                </button>
            </div>
        </div>
    </div>
@endif
