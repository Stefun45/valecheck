<?php

namespace App\Livewire\VehicleCheck;

use App\Models\VehicleCheck;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ShowCheck extends Component
{
    public VehicleCheck $vehicleCheck;

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
