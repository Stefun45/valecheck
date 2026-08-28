<?php

namespace App\Providers;

use App\Services\Ai\AiProvider;
use App\Services\Ai\AnthropicProvider;
use App\Services\Ai\OpenAiProvider;
use App\Services\ListingImport\AutoTraderListingProvider;
use App\Services\ListingImport\CopartListingProvider;
use App\Services\ListingImport\EbayListingProvider;
use App\Services\ListingImport\GenericPublicListingProvider;
use App\Services\ListingImport\ListingImportService;
use App\Services\OneAuto\MotHistoryAndTaxStatusFetcher;
use App\Services\OneAuto\OneAutoClient;
use App\Services\RegistrationLookup\DvlaVesProvider;
use App\Services\RegistrationLookup\MockDvlaProvider;
use App\Services\RegistrationLookup\OneAutoBasicProvider;
use App\Services\RegistrationLookup\VehicleSpecPreviewProvider;
use App\Services\SalvageAuction\MockSalvageAuctionProvider;
use App\Services\SalvageAuction\OneAutoSalvageAuctionProvider;
use App\Services\SalvageAuction\SalvageAuctionProvider;
use App\Services\Valuation\MarketValuationProvider;
use App\Services\Valuation\MockMarketValuationProvider;
use App\Services\Valuation\OneAutoMarketValuationProvider;
use App\Services\VehicleData\MockVehicleDataProvider;
use App\Services\VehicleData\OneAutoVehicleDataProvider;
use App\Services\VehicleData\VehicleDataProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OneAutoClient::class, function () {
            return new OneAutoClient(
                config('valecheck.vehicle_data.oneauto.api_key'),
                config('valecheck.vehicle_data.oneauto.base_url'),
            );
        });

        $this->app->bind(VehicleDataProvider::class, function ($app) {
            return match (strtolower((string) config('valecheck.vehicle_data.provider'))) {
                'oneauto' => new OneAutoVehicleDataProvider(
                    $app->make(OneAutoClient::class),
                    $app->make(MotHistoryAndTaxStatusFetcher::class),
                ),
                default => new MockVehicleDataProvider,
            };
        });

        $this->app->bind(AiProvider::class, function () {
            return match (strtolower((string) config('valecheck.ai.provider'))) {
                'openai' => new OpenAiProvider(
                    config('services.openai.api_key'),
                    config('valecheck.ai.model'),
                ),
                default => new AnthropicProvider(
                    config('services.anthropic.api_key'),
                    config('valecheck.ai.model'),
                ),
            };
        });

        $this->app->bind(MarketValuationProvider::class, function ($app) {
            return match (strtolower((string) config('valecheck.vehicle_data.provider'))) {
                'oneauto' => new OneAutoMarketValuationProvider($app->make(OneAutoClient::class)),
                default => new MockMarketValuationProvider,
            };
        });

        // Checked in this order — named marketplace providers get first
        // refusal, GenericPublicListingProvider is the catch-all fallback.
        $this->app->singleton(ListingImportService::class, function ($app) {
            return new ListingImportService([
                $app->make(EbayListingProvider::class),
                $app->make(AutoTraderListingProvider::class),
                $app->make(CopartListingProvider::class),
                $app->make(GenericPublicListingProvider::class),
            ]);
        });

        $this->app->bind(VehicleSpecPreviewProvider::class, function ($app) {
            return match (strtolower((string) config('valecheck.registration_lookup.provider'))) {
                'dvla' => new DvlaVesProvider(
                    config('valecheck.registration_lookup.dvla.api_key'),
                    config('valecheck.registration_lookup.dvla.base_url'),
                ),
                'oneauto' => new OneAutoBasicProvider($app->make(MotHistoryAndTaxStatusFetcher::class)),
                default => new MockDvlaProvider,
            };
        });

        $this->app->bind(SalvageAuctionProvider::class, function ($app) {
            return match (strtolower((string) config('valecheck.vehicle_data.provider'))) {
                'oneauto' => new OneAutoSalvageAuctionProvider($app->make(OneAutoClient::class)),
                default => new MockSalvageAuctionProvider,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
