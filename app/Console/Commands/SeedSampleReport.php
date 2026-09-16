<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VehicleCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One-off operational command, not a customer-facing feature: copies a
 * real completed Plus check onto a dedicated, non-billable "sample" owner
 * account, flagged is_sample so it's excluded from every business metric,
 * for display on the public /sample-report SEO page. Mirrors
 * SetupExperianDemoAccount's replicate() pattern exactly.
 *
 * Safe to re-run — any previous sample is deleted first (cascading to its
 * cloned relations/report via the foreign key), so there's only ever one.
 * The source check and its real owner are never touched.
 */
class SeedSampleReport extends Command
{
    protected $signature = 'demo:seed-sample-report {registration}';

    protected $description = 'Copy a completed Plus check onto a dedicated sample account for the public /sample-report page.';

    private const COPIED_RELATIONS = [
        'history', 'valuation', 'taxCost', 'salvageAuctionCheck',
        'damageAnalysis', 'repairEstimate', 'bidRecommendation',
    ];

    public function handle(): int
    {
        $registration = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $this->argument('registration')));

        $source = VehicleCheck::where('registration', $registration)
            ->where('status', VehicleCheck::STATUS_COMPLETED)
            ->where('type', VehicleCheck::TYPE_PLUS)
            ->where('is_sample', false)
            ->latest()
            ->first();

        if (! $source) {
            $this->error("No completed ValeCheck Plus check found for {$registration}.");

            return self::FAILURE;
        }

        $user = User::firstOrCreate(
            ['email' => 'sample-report@valecheck.internal'],
            ['name' => 'Sample Report', 'password' => Str::random(40), 'email_verified_at' => now()]
        );

        VehicleCheck::where('user_id', $user->id)->where('is_sample', true)->get()->each->delete();

        $newCheck = DB::transaction(function () use ($source, $user) {
            $newCheck = $source->replicate([
                'public_id', 'payment_id', 'credit_transaction_id',
                'upgrade_payment_id', 'upgraded_at', 'discount_code', 'listing_import_id',
            ]);
            $newCheck->user_id = $user->id;
            $newCheck->is_sample = true;
            $newCheck->save();

            foreach (self::COPIED_RELATIONS as $relation) {
                if ($related = $source->{$relation}) {
                    $clone = $related->replicate();
                    $clone->vehicle_check_id = $newCheck->id;
                    $clone->save();
                }
            }

            if ($report = $source->report) {
                $clone = $report->replicate(['pdf_path', 'pdf_disk']);
                $clone->vehicle_check_id = $newCheck->id;
                $clone->save();
            }

            return $newCheck;
        });

        $this->info('Sample report ready.');
        $this->info('URL: '.route('sample-report'));
        $this->info("(Sourced from check #{$source->id}, {$registration} — original untouched.)");

        return self::SUCCESS;
    }
}
