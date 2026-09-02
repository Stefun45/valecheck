<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VehicleCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off operational command, not a customer-facing feature: creates (or
 * reuses) a demo account for an external reviewer — currently Experian's
 * approval process — and copies a completed check onto it, so the reviewer
 * can log in and see a real report without either sharing the site
 * password/an existing customer's login, or exposing that report publicly.
 *
 * The original check, and everything belonging to its real owner, is left
 * completely untouched — this creates an independent copy, never moves or
 * reassigns the source.
 */
class SetupExperianDemoAccount extends Command
{
    protected $signature = 'demo:setup-experian-account {registration} {email} {password}';

    protected $description = 'Create/reuse a demo account and copy a completed check onto it for an external reviewer.';

    private const COPIED_RELATIONS = [
        'history', 'valuation', 'taxCost', 'salvageAuctionCheck',
        'damageAnalysis', 'repairEstimate', 'bidRecommendation',
    ];

    public function handle(): int
    {
        $registration = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $this->argument('registration')));

        $source = VehicleCheck::where('registration', $registration)
            ->where('status', VehicleCheck::STATUS_COMPLETED)
            ->latest()
            ->first();

        if (! $source) {
            $this->error("No completed check found for {$registration}.");

            return self::FAILURE;
        }

        $email = $this->argument('email');
        $password = $this->argument('password');

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Experian Reviewer', 'password' => $password, 'email_verified_at' => now()]
        );

        if (! $user->wasRecentlyCreated) {
            // Reusing an existing demo account — always reset the password
            // to exactly what's about to go in the document sent to the
            // reviewer, so it can never silently be out of date.
            $user->forceFill(['password' => $password])->save();
        }

        $newCheck = DB::transaction(function () use ($source, $user) {
            // Exclude public_id so a fresh one is generated on save (see
            // VehicleCheck::booted) - copying it verbatim would collide
            // with the source's unique public_id. Billing/import fields
            // are excluded too - they identify a specific real financial
            // transaction or import belonging to the original owner, not
            // something a demo copy should carry.
            $newCheck = $source->replicate([
                'public_id', 'payment_id', 'credit_transaction_id',
                'upgrade_payment_id', 'upgraded_at', 'discount_code', 'listing_import_id',
            ]);
            $newCheck->user_id = $user->id;
            $newCheck->save();

            foreach (self::COPIED_RELATIONS as $relation) {
                if ($related = $source->{$relation}) {
                    $clone = $related->replicate();
                    $clone->vehicle_check_id = $newCheck->id;
                    $clone->save();
                }
            }

            // The PDF itself isn't copied - pdf_path/pdf_disk are excluded
            // so ReportPdfService regenerates a fresh one (under the new
            // check's own id) the first time it's requested.
            if ($report = $source->report) {
                $clone = $report->replicate(['pdf_path', 'pdf_disk']);
                $clone->vehicle_check_id = $newCheck->id;
                $clone->save();
            }

            return $newCheck;
        });

        $this->info('Demo account ready.');
        $this->info("Email: {$email}");
        $this->info("Password: {$password}");
        $this->info('Report URL: '.route('vehicle-checks.show', $newCheck));

        return self::SUCCESS;
    }
}
