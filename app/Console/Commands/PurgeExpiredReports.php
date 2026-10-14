<?php

namespace App\Console\Commands;

use App\Models\VehicleCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Permanently deletes report content — photographs, AI analysis, valuation/
 * repair/bid figures and the PDF — for checks past their retention period.
 * The vehicle_checks row itself is kept (with purged_at set) as a
 * lightweight history stub, not hard-deleted: Payment and CreditTransaction
 * rows reference it and have their own, independent retention needs, and a
 * customer who paid for a report shouldn't see it vanish without a trace.
 */
class PurgeExpiredReports extends Command
{
    protected $signature = 'reports:purge-expired';

    protected $description = 'Permanently delete report content for checks past their retention period.';

    public function handle(): int
    {
        $checks = VehicleCheck::query()
            ->where('status', VehicleCheck::STATUS_COMPLETED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNull('purged_at')
            ->get();

        $purged = 0;

        foreach ($checks as $check) {
            try {
                DB::transaction(fn () => $this->purge($check));
                $purged++;
            } catch (Throwable $e) {
                Log::error("Failed to purge expired report for VehicleCheck #{$check->id}: {$e->getMessage()}");
            }
        }

        $this->info("Purged {$purged} of {$checks->count()} expired report(s).");

        return self::SUCCESS;
    }

    private function purge(VehicleCheck $check): void
    {
        foreach ($check->images as $image) {
            Storage::disk($image->disk)->delete($image->path);
            $image->delete();
        }

        if ($report = $check->report) {
            if ($report->pdf_path) {
                Storage::disk($report->pdf_disk)->delete($report->pdf_path);
            }

            $report->delete();
        }

        // Each delete() issues a real DELETE statement, so the DB-level
        // cascadeOnDelete() FKs already declared on damage_findings/
        // repair_items clean up their children automatically.
        $check->damageAnalysis?->delete();
        $check->repairEstimate?->delete();
        $check->bidRecommendation?->delete();
        $check->valuation?->delete();
        $check->history?->delete();

        $check->update(['purged_at' => now()]);
    }
}
