<?php

namespace App\Services\OneAuto;

use App\Models\ProviderEndpointCost;
use App\Models\ProviderLookupLog;
use Closure;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin shared HTTP client for One Auto API (https://docs.oneautoapi.com/).
 * Every provider method that needs live data goes through exactly one
 * named call here — GET {base_url}/{endpoint}?vehicle_registration_mark=...
 * with an `x-api-key` header — so the number of API calls a report makes
 * stays greppable and countable, per call cost control.
 *
 * Every call is logged to provider_lookup_logs here, not by the callers —
 * centralising it means no call can slip through unlogged, which is what
 * makes the admin-visible "API calls made" figure actually trustworthy.
 */
class OneAutoClient
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly ?string $baseUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     * @param  (Closure(?int): float)|null  $costResolver  Overrides the
     *                                                     default flat ProviderEndpointCost lookup when the cost of a call
     *                                                     actually depends on how it resolved (e.g. HTTP status), rather
     *                                                     than always being the endpoint's flat configured rate. Called
     *                                                     with the HTTP status of the response (null if the request never
     *                                                     got one, e.g. a connection failure). Every existing caller omits
     *                                                     this and keeps the flat-lookup behaviour unchanged.
     * @return array<string, mixed> the decoded "result" object
     */
    public function get(string $endpoint, string $registration, array $query, ?int $vehicleCheckId = null, ?Closure $costResolver = null): array
    {
        if (empty($this->apiKey) || empty($this->baseUrl)) {
            throw new RuntimeException(
                'One Auto API is not configured. Set ONEAUTO_API_KEY and ONEAUTO_BASE_URL, '
                .'or set VEHICLE_DATA_PROVIDER=mock / REGISTRATION_LOOKUP_PROVIDER=mock for local development.'
            );
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get(rtrim($this->baseUrl, '/').'/'.ltrim($endpoint, '/'), $query);
        } catch (\Throwable $e) {
            $this->log($endpoint, $registration, $vehicleCheckId, ProviderLookupLog::STATUS_FAILED, null, $e->getMessage(), $costResolver);

            throw new OneAutoApiException($endpoint, null, "One Auto API request to [{$endpoint}] could not be completed: {$e->getMessage()}");
        }

        if ($response->failed()) {
            $this->log($endpoint, $registration, $vehicleCheckId, ProviderLookupLog::STATUS_FAILED, $response->status(), $response->body(), $costResolver);

            throw new OneAutoApiException(
                $endpoint,
                $response->status(),
                "One Auto API request to [{$endpoint}] failed with status {$response->status()}: {$response->body()}",
            );
        }

        if ($response->json('success') === false) {
            $errorMessage = $response->json('error', 'unknown error');
            $this->log($endpoint, $registration, $vehicleCheckId, ProviderLookupLog::STATUS_FAILED, $response->status(), $errorMessage, $costResolver);

            throw new OneAutoApiException(
                $endpoint,
                $response->status(),
                "One Auto API returned an error for [{$endpoint}]: {$errorMessage}",
            );
        }

        $this->log($endpoint, $registration, $vehicleCheckId, ProviderLookupLog::STATUS_SUCCESS, $response->status(), null, $costResolver);

        return $response->json('result', []);
    }

    /**
     * @param  (Closure(?int): float)|null  $costResolver
     */
    private function log(string $endpoint, string $registration, ?int $vehicleCheckId, string $status, ?int $httpStatus, ?string $errorMessage, ?Closure $costResolver = null): void
    {
        ProviderLookupLog::create([
            'provider' => 'oneauto',
            'endpoint' => $endpoint,
            'registration' => $registration,
            'vehicle_check_id' => $vehicleCheckId,
            'status' => $status,
            'http_status' => $httpStatus,
            'error_message' => $errorMessage ? str($errorMessage)->limit(255)->toString() : null,
            // Snapshotted now, at the moment this call happened — never
            // re-read later, so an admin editing costs going forward can't
            // silently rewrite what a past call is recorded as costing.
            // A $costResolver, when given, overrides this flat lookup —
            // see salvageguide/thirdpartyimages, the one endpoint whose
            // real billing depends on the HTTP status rather than always
            // charging the endpoint's flat configured rate.
            'cost_net' => $costResolver !== null
                ? $costResolver($httpStatus)
                : ProviderEndpointCost::where('endpoint', $endpoint)->value('cost_net'),
        ]);
    }
}
