<?php

namespace App\Livewire;

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
 */
class RegistrationQuickLook extends Component
{
    #[Modelable]
    public string $registration = '';

    public ?array $preview = null;

    public string $status = 'idle';

    public function check(): void
    {
        $this->preview = null;
        $normalised = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $this->registration));

        if (strlen($normalised) < 5 || strlen($normalised) > 8) {
            $this->status = 'invalid';

            return;
        }

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
