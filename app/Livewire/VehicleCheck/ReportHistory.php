<?php

namespace App\Livewire\VehicleCheck;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ReportHistory extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.vehicle-check.report-history', [
            'checks' => auth()->user()->vehicleChecks()->with('vehicle')->latest()->paginate(15),
        ]);
    }
}
