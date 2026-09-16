<?php

namespace App\Livewire;

use App\Models\FreeLookupLog;
use App\Services\RegistrationLookup\FreeLookupGuard;
use App\Services\RegistrationLookup\VehicleSpecPreviewProvider;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Throwable;

/**
 * "Is this your vehicle?" — fired explicitly by a "Check Vehicle" click
 * (never on every keystroke) since a real lookup can cost real money via
 * One Auto's MOT History & Tax Status call. Confirming navigates into the
 * full check flow; rejecting lets the user correct the plate without
 * spending another lookup until they click again.
 *
 * Free, unauthenticated, and one real billable API call per distinct
 * plate — a prime scripting target. Throttled per IP via FreeLookupGuard
 * (shared with the start-check page's own preview) rather than a CAPTCHA:
 * no real visitor plausibly needs more than a handful of lookups an hour,
 * so this stays invisible to genuine use while capping the worst-case
 * cost of a script cycling through plates from one address.
 */
class RegistrationQuickLook extends Component
{
    #[Modelable]
    public string $registration = '';

    public ?array $preview = null;

    public string $status = 'idle';

    public function check(FreeLookupGuard $guard): void
    {
        $this->preview = null;
        $normalised = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $this->registration));

        if (strlen($normalised) < 5 || strlen($normalised) > 8) {
            $this->status = 'invalid';

            return;
        }

        if ($guard->tooManyAttempts()) {
            $this->status = 'rate_limited';

            return;
        }

        $guard->recordAttempt(FreeLookupLog::SOURCE_HOMEPAGE);

        $this->registration = $normalised;
        $this->status = 'loading';

        try {
            $result = app(VehicleSpecPreviewProvider::class)->preview($normalised);

            if ($result === null) {
                $this->status = 'not_found';

                return;
            }

            $this->preview = [
                'registration' => $result->registration,
                'make' => $result->make,
                'model' => $result->model,
                'colour' => $result->colour,
                'fuel_type' => $result->fuelType,
                'year' => $result->yearOfManufacture,
                'engine_capacity' => $result->engineCapacity,
                'mot_status' => $result->motStatus,
                'tax_status' => $result->taxStatus,
                'tax_expiry_date' => $result->taxExpiryDate,
                'mot_history' => $result->motHistory,
            ];
            $this->status = 'found';
        } catch (Throwable) {
            $this->status = 'unavailable';
        }
    }

    public function reject(): void
    {
        $this->preview = null;
        $this->status = 'idle';
    }

    public function confirm(): void
    {
        $this->redirect(route('vehicle-checks.start', ['registration' => $this->registration]), navigate: false);
    }

    public function usingMockData(): bool
    {
        return config('valecheck.registration_lookup.provider') === 'mock';
    }

    public function render()
    {
        return view('livewire.registration-quick-look');
    }
}
