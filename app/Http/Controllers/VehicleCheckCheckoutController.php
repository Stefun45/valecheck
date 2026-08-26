<?php

namespace App\Http\Controllers;

use App\Models\VehicleCheck;
use App\Services\Payments\StripeCheckoutService;

class VehicleCheckCheckoutController extends Controller
{
    public function show(VehicleCheck $vehicleCheck, StripeCheckoutService $checkoutService)
    {
        abort_unless($vehicleCheck->user_id === auth()->id(), 403);
        abort_if($vehicleCheck->status !== VehicleCheck::STATUS_PENDING, 404);

        if (empty(config('cashier.secret'))) {
            return view('checkout.pending', ['vehicleCheck' => $vehicleCheck]);
        }

        return $checkoutService->checkoutForVehicleCheck($vehicleCheck)->redirect();
    }
}
