<?php

namespace App\Http\Controllers;

use App\Models\VehicleCheck;
use App\Services\Payments\StripeCheckoutService;

class VehicleCheckUpgradeController extends Controller
{
    public function show(VehicleCheck $vehicleCheck, StripeCheckoutService $checkoutService)
    {
        abort_unless($vehicleCheck->user_id === auth()->id(), 403);
        abort_unless($vehicleCheck->isUpgradeable(), 404);

        if (empty(config('cashier.secret'))) {
            return view('checkout.pending', ['vehicleCheck' => $vehicleCheck]);
        }

        return $checkoutService->checkoutForVehicleCheckUpgrade($vehicleCheck)->redirect();
    }
}
