<?php

namespace App\Services\Ordering;

use App\Models\ListingImage;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCheck;
use App\Services\Credits\CreditLedgerService;
use App\Services\Pipeline\VehicleCheckPipeline;
use App\Services\Pricing\PricingService;
use Illuminate\Support\Facades\DB;

/**
 * Decides how a requested vehicle check gets funded (credit, an
 * admin-set £0 account price, or a one-off Stripe purchase) and either
 * dispatches the processing pipeline immediately or leaves the check
 * "pending" for a Payment to unlock it.
 *
 * ValeCheck Plus and ValeCheck Rebuild can be funded by credit - for Plus
 * this is one unified balance (CreditLedgerService) whether it came from
 * a subscription's monthly allowance, an additional-credit top-up, or a
 * purchased pack; the base ValeCheck product is otherwise always a
 * one-off purchase, unless a per-account price override brings it to £0.
 */
class VehicleCheckOrderService
{
    public function __construct(
        private readonly CreditLedgerService $ledger,
        private readonly VehicleCheckPipeline $pipeline,
        private readonly PricingService $pricing,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function submit(User $user, string $type, array $attributes): VehicleCheck
    {
        return DB::transaction(function () use ($user, $type, $attributes) {
            $vehicle = Vehicle::firstOrCreate(['registration' => strtoupper(preg_replace('/\s+/', '', $attributes['registration']))]);

            if ($type === VehicleCheck::TYPE_PLUS) {
                $reusable = $this->findReusableExistingCheck($user, $vehicle);

                if ($reusable) {
                    return $reusable;
                }
            }

            $fundingSource = $this->determineFundingSource($user, $type);

            $check = VehicleCheck::create([
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'type' => $type,
                'status' => VehicleCheck::STATUS_PENDING,
                'funding_source' => $fundingSource,
                'registration' => $vehicle->registration,
                'mileage' => $attributes['mileage'] ?? null,
                'listing_url' => $attributes['listing_url'] ?? null,
                'auction_name' => $attributes['auction_name'] ?? null,
                'current_bid' => $attributes['current_bid'] ?? null,
                'asking_price' => $attributes['asking_price'] ?? null,
                'listing_description' => $attributes['listing_description'] ?? null,
                'listing_import_id' => $attributes['listing_import_id'] ?? null,
                'listing_data_sources' => $attributes['listing_data_sources'] ?? null,
                'discount_code' => $attributes['discount_code'] ?? null,
            ]);

            $position = 0;

            foreach ($attributes['images'] ?? [] as $storedPath) {
                $check->images()->create([
                    'disk' => 'local',
                    'path' => $storedPath,
                    'position' => $position++,
                    'source' => 'uploaded',
                ]);
            }

            foreach (ListingImage::whereIn('id', $attributes['imported_image_ids'] ?? [])->get() as $listingImage) {
                $check->images()->create([
                    'disk' => $listingImage->disk,
                    'path' => $listingImage->path,
                    'position' => $position++,
                    'source' => 'imported',
                ]);
            }

            if ($fundingSource === 'credit' && $type === VehicleCheck::TYPE_PLUS) {
                // Deliberately NOT consumed here - a Plus report's credit
                // (whether from a subscription's monthly allowance or a
                // purchased pack, both the same unified balance now) is
                // only ever consumed once GenerateReport confirms the
                // report actually generated. See GenerateReport::handle()
                // and FailedVehicleCheckHandler, which has nothing to
                // refund for this type any more since nothing was deducted
                // up front.
                $this->pipeline->dispatch($check);
            } elseif ($fundingSource === 'credit') {
                // Rebuild credit packs keep the original deduct-then-refund
                // behaviour - the new consume-only-on-success requirement
                // is specific to Plus subscription credits, not part of
                // this change.
                $transaction = $this->ledger->consumeCredit($user, $type, $check);
                $check->update(['credit_transaction_id' => $transaction->id]);
                $this->pipeline->dispatch($check);
            } elseif ($fundingSource === 'free') {
                // An admin-set £0 account price - no credit or payment of
                // any kind to record, just process it directly.
                $this->pipeline->dispatch($check);
            }

            // 'purchase' funding source is left pending - the pipeline is
            // dispatched once Stripe confirms payment (see StripeWebhookController).

            return $check->fresh();
        });
    }

    private function determineFundingSource(User $user, string $type): string
    {
        // Checked first, and for every type including the base ValeCheck
        // product - an admin-set £0 account price should never route to
        // Stripe (which doesn't support a genuine £0 Checkout Session
        // anyway) regardless of what type it is.
        if ($this->pricing->forProduct($type, $user)->gross <= 0.0) {
            return 'free';
        }

        if (! in_array($type, [VehicleCheck::TYPE_PLUS, VehicleCheck::TYPE_REBUILD], true)) {
            return 'purchase';
        }

        if ($this->ledger->hasCredit($user, $type)) {
            return 'credit';
        }

        return 'purchase';
    }

    /**
     * A customer re-checking a vehicle they already have a completed Plus
     * report for within the reuse window sees that existing report - no
     * new check, no credit or payment touched, no repeat provider calls.
     * Only ever applies to Plus (Rebuild's fresh damage-photo analysis
     * doesn't make sense to reuse) and only a customer's own prior check.
     */
    private function findReusableExistingCheck(User $user, Vehicle $vehicle): ?VehicleCheck
    {
        return VehicleCheck::where('user_id', $user->id)
            ->where('vehicle_id', $vehicle->id)
            ->where('type', VehicleCheck::TYPE_PLUS)
            ->where('status', VehicleCheck::STATUS_COMPLETED)
            ->where('created_at', '>=', now()->subDays((int) config('valecheck.reports.reuse_window_days')))
            ->latest()
            ->first();
    }
}
