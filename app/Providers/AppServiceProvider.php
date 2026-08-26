<?php

namespace App\Providers;

use App\Listeners\SendWelcomeEmail;
use App\Services\Ai\AiProvider;
use App\Services\Ai\AnthropicProvider;
use App\Services\Ai\OpenAiProvider;
use App\Services\ListingImport\AutoTraderListingProvider;
use App\Services\ListingImport\CopartListingProvider;
use App\Services\ListingImport\EbayListingProvider;
use App\Services\ListingImport\GenericPublicListingProvider;
use App\Services\ListingImport\ListingImportService;
use App\Services\RegistrationLookup\DvlaVesProvider;
use App\Services\RegistrationLookup\MockDvlaProvider;
use App\Services\RegistrationLookup\VehicleMaticBasicProvider;
use App\Services\RegistrationLookup\VehicleSpecPreviewProvider;
use App\Services\Valuation\MarketValuationProvider;
use App\Services\Valuation\MockMarketValuationProvider;
use App\Services\VehicleData\MockVehicleDataProvider;
use App\Services\VehicleData\VehicleDataProvider;
use App\Services\VehicleData\VehicleMaticProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(VehicleDataProvider::class, function () {
            return match (strtolower((string) config('valecheck.vehicle_data.provider'))) {
                'vehiclematic' => new VehicleMaticProvider(
                    config('valecheck.vehicle_data.vehiclematic.api_key'),
                    config('valecheck.vehicle_data.vehiclematic.base_url'),
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

        $this->app->bind(MarketValuationProvider::class, MockMarketValuationProvider::class);

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

        $this->app->bind(VehicleSpecPreviewProvider::class, function () {
            return match (strtolower((string) config('valecheck.registration_lookup.provider'))) {
                'dvla' => new DvlaVesProvider(
                    config('valecheck.registration_lookup.dvla.api_key'),
                    config('valecheck.registration_lookup.dvla.base_url'),
                ),
                'vehiclematic' => new VehicleMaticBasicProvider(
                    config('valecheck.registration_lookup.vehiclematic.api_key'),
                    config('valecheck.registration_lookup.vehiclematic.base_url'),
                ),
                default => new MockDvlaProvider,
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Registered::class, SendWelcomeEmail::class);
    }
}
