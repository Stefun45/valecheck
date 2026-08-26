<?php

namespace App\Services\Ordering;

use App\Models\ListingImage;
use App\Models\SubscriptionUsage;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCheck;
use App\Services\Credits\CreditLedgerService;
use App\Services\Pipeline\VehicleCheckPipeline;
use Illuminate\Support\Facades\DB;

/**
 * Decides how a requested vehicle check gets funded (purchased credit,
 * subscription allowance, or a one-off Stripe purchase) and either
 * dispatches the processing pipeline immediately or leaves the check
 * "pending" for a Payment to unlock it.
 *
 * Only ValeCheck Rebuild (the specialist, highest-value product) can be
 * funded by purchased credits or a subscription allowance — ValeCheck and
 * ValeCheck Plus are always a one-off purchase, same as before.
 */
class VehicleCheckOrderService
{
    public function __construct(
        private readonly CreditLedgerService $ledger,
        private readonly VehicleCheckPipeline $pipeline,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function submit(User $user, string $type, array $attributes): VehicleCheck
    {
        return DB::transaction(function () use ($user, $type, $attributes) {
            $vehicle = Vehicle::firstOrCreate(['registration' => strtoupper(preg_replace('/\s+/', '', $attributes['registration']))]);

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

            if ($fundingSource === 'credit' || $fundingSource === 'free') {
                $transaction = $this->ledger->consumeCredit($user, $type, $check);
                $check->update(['credit_transaction_id' => $transaction->id]);
                $this->pipeline->dispatch($check);
            } elseif ($fundingSource === 'subscription') {
                $this->consumeSubscriptionAllowance($user, $type);
                $this->pipeline->dispatch($check);
            }

            // 'purchase' funding source is left pending — the pipeline is
            // dispatched once Stripe confirms payment (see StripeWebhookController).

            return $check->fresh();
        });
    }

    private function determineFundingSource(User $user, string $type): string
    {
        if ($type !== VehicleCheck::TYPE_REBUILD) {
            return 'purchase';
        }

        if ($this->ledger->hasCredit($user, $type)) {
            return 'credit';
        }

        if ($this->activeSubscriptionUsage($user, $type)?->hasRemainingAllowance()) {
            return 'subscription';
        }

        return 'purchase';
    }

    private function activeSubscriptionUsage(User $user, string $reportType): ?SubscriptionUsage
    {
        return SubscriptionUsage::where('user_id', $user->id)
            ->where('report_type', $reportType)
            ->whereDate('period_start', '<=', now())
            ->whereDate('period_end', '>=', now())
            ->first();
    }

    private function consumeSubscriptionAllowance(User $user, string $reportType): void
    {
        $this->activeSubscriptionUsage($user, $reportType)?->increment('used');
    }
}
