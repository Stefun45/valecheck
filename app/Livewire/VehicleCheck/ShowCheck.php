<?php

namespace App\Livewire\VehicleCheck;

use App\Models\VehicleCheck;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ShowCheck extends Component
{
    public VehicleCheck $vehicleCheck;

    public string $vinToVerify = '';

    public ?bool $vinMatchResult = null;

    public function mount(VehicleCheck $vehicleCheck): void
    {
        $this->authorize('view', $vehicleCheck);

        $this->vehicleCheck = $vehicleCheck;
    }

    public function isProcessing(): bool
    {
        return in_array($this->vehicleCheck->fresh()->status, [
            VehicleCheck::STATUS_PENDING,
            VehicleCheck::STATUS_PROCESSING,
        ], true);
    }

    /**
     * Lets a customer confirm the V5C/dashboard VIN in front of them
     * matches what the report holds, without ever sending the real VIN
     * to the browser — only this boolean result leaves the server.
     */
    public function verifyVin(): void
    {
        $submitted = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $this->vinToVerify));
        $realVin = $this->vehicleCheck->vehicle->vin;

        $this->vinMatchResult = $submitted !== '' && $realVin !== null && strtoupper($realVin) === $submitted;
    }

    public function render()
    {
        $this->vehicleCheck->refresh();

        $view = match (true) {
            $this->vehicleCheck->status === VehicleCheck::STATUS_FAILED => 'livewire.vehicle-check.partials.failed',
            $this->vehicleCheck->status === VehicleCheck::STATUS_COMPLETED && $this->vehicleCheck->isRebuild() => 'livewire.vehicle-check.partials.rebuild-report',
            $this->vehicleCheck->status === VehicleCheck::STATUS_COMPLETED && $this->vehicleCheck->isPlus() => 'livewire.vehicle-check.partials.plus-report',
            $this->vehicleCheck->status === VehicleCheck::STATUS_COMPLETED => 'livewire.vehicle-check.partials.check-report',
            default => 'livewire.vehicle-check.partials.progress',
        };

        return view('livewire.vehicle-check.show-check', ['contentView' => $view]);
    }
}
