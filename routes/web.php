<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\CreatorController;
use App\Http\Controllers\Admin\DiscountCodeController;
use App\Http\Controllers\Admin\ProductPriceController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportPdfController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\VehicleCheckCheckoutController;
use App\Livewire\VehicleCheck\ReportHistory;
use App\Livewire\VehicleCheck\ShowCheck;
use App\Livewire\VehicleCheck\StartCheck;
use App\Services\Pricing\PricingService;
use Illuminate\Support\Facades\Route;

Route::get('/', function (PricingService $pricing) {
    return view('welcome', [
        'checkPrice' => $pricing->forCheck(),
        'plusPrice' => $pricing->forPlus(),
        'rebuildPrice' => $pricing->forRebuild(),
    ]);
})->name('welcome');

Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Public: anyone can look up a vehicle and see Check vs Plus vs Rebuild pricing
// without an account. Signing up is only required at the point of actually
// getting a report (see StartCheck::submit()).
Route::get('check', StartCheck::class)->name('vehicle-checks.start');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('checks/{vehicleCheck}', ShowCheck::class)->name('vehicle-checks.show');
    Route::get('checks/{vehicleCheck}/pdf', [ReportPdfController::class, 'download'])->name('vehicle-checks.pdf');

    Route::get('reports', ReportHistory::class)->name('reports.index');

    Route::get('checkout/vehicle-check/{vehicleCheck}', [VehicleCheckCheckoutController::class, 'show'])
        ->name('checkout.vehicle-check');

    Route::post('billing/credit-pack', [BillingController::class, 'creditPack'])->name('billing.credit-pack');
    Route::post('billing/subscribe', [BillingController::class, 'subscription'])->name('billing.subscribe');
});

Route::get('admin', [AdminDashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.dashboard');

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('affiliates', CreatorController::class)->parameters(['affiliates' => 'creator'])->except(['destroy']);
    Route::post('affiliates/{creator}/toggle', [CreatorController::class, 'toggle'])->name('affiliates.toggle');
    Route::post('commissions/{commission}/mark-paid', [CommissionController::class, 'markPaid'])->name('commissions.mark-paid');

    Route::resource('discount-codes', DiscountCodeController::class)->parameters(['discount-codes' => 'discountCode'])->except(['destroy', 'show']);
    Route::post('discount-codes/{discountCode}/toggle', [DiscountCodeController::class, 'toggle'])->name('discount-codes.toggle');

    Route::get('pricing', [ProductPriceController::class, 'edit'])->name('pricing.edit');
    Route::put('pricing', [ProductPriceController::class, 'update'])->name('pricing.update');
});

require __DIR__.'/auth.php';
