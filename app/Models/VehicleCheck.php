<?php

namespace App\Models;

use App\DataTransferObjects\VehicleData;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id', 'vehicle_id', 'type', 'status', 'stage', 'funding_source',
    'payment_id', 'credit_transaction_id', 'registration', 'mileage',
    'listing_url', 'auction_name', 'current_bid', 'asking_price',
    'listing_description', 'listing_import_id', 'listing_data_sources', 'discount_code',
    'failure_reason', 'started_at', 'completed_at', 'expires_at', 'purged_at',
    'upgrade_payment_id', 'upgraded_at', 'vehicle_image_disk', 'vehicle_image_path',
])]
class VehicleCheck extends Model
{
    use HasFactory;

    public const TYPE_CHECK = 'check';

    public const TYPE_PLUS = 'plus';

    public const TYPE_REBUILD = 'rebuild';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    public const STAGE_LABELS = [
        'retrieving_history' => 'Checking vehicle history...',
        'retrieving_valuation' => 'Checking market value...',
        'retrieving_tax_cost' => 'Checking tax cost...',
        'retrieving_salvage_auction_history' => 'Checking salvage auction history...',
        'analysing_images' => 'Analysing photographs...',
        'calculating_repair' => 'Estimating repairs...',
        'calculating_maximum_bid' => 'Estimating maximum bid...',
        'calculating_deal_score' => 'Scoring the deal...',
        'generating_report' => 'Generating report...',
    ];

    protected function casts(): array
    {
        return [
            'current_bid' => 'decimal:2',
            'asking_price' => 'decimal:2',
            'listing_data_sources' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
            'purged_at' => 'datetime',
            'upgraded_at' => 'datetime',
        ];
    }

    /**
     * Only a completed Check can be upgraded to Plus — anything already
     * Plus/Rebuild, or not yet completed, has nothing to upgrade.
     */
    public function isUpgradeable(): bool
    {
        return $this->type === self::TYPE_CHECK && $this->status === self::STATUS_COMPLETED;
    }

    public function hasVehicleImage(): bool
    {
        return $this->vehicle_image_path !== null
            && Storage::disk($this->vehicle_image_disk)->exists($this->vehicle_image_path);
    }

    /**
     * Short-lived signed URL, matching the same pattern used for report
     * PDFs — regenerated on every render rather than a stored permanent
     * link, so it works identically regardless of storage disk.
     */
    public function vehicleImageUrl(): ?string
    {
        return $this->hasVehicleImage()
            ? Storage::disk($this->vehicle_image_disk)->temporaryUrl($this->vehicle_image_path, now()->addMinutes(10))
            : null;
    }

    /**
     * Base64 data URI for the PDF — dompdf has isRemoteEnabled disabled
     * (see ReportPdfService), so a normal image URL (even our own) can't
     * be fetched; embedding the bytes directly sidesteps that entirely.
     */
    public function vehicleImageDataUri(): ?string
    {
        if (! $this->hasVehicleImage()) {
            return null;
        }

        $disk = Storage::disk($this->vehicle_image_disk);
        $mime = $disk->mimeType($this->vehicle_image_path) ?: 'image/png';
        $contents = $disk->get($this->vehicle_image_path);

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    public function isPurged(): bool
    {
        return $this->purged_at !== null;
    }

    public function isRebuild(): bool
    {
        return $this->type === self::TYPE_REBUILD;
    }

    public function isPlus(): bool
    {
        return $this->type === self::TYPE_PLUS;
    }

    /**
     * Plus and Rebuild both include market valuation; only Check doesn't.
     */
    public function needsValuation(): bool
    {
        return $this->type !== self::TYPE_CHECK;
    }

    /**
     * Only Rebuild includes AI damage analysis, repair costing, maximum bid
     * and deal score — the specialist damaged-vehicle decision tooling.
     */
    public function needsDamageAnalysis(): bool
    {
        return $this->isRebuild();
    }

    public function stageLabel(): ?string
    {
        return self::STAGE_LABELS[$this->stage] ?? null;
    }

    /**
     * Reconstruct the VehicleData DTO from what RetrieveVehicleHistory
     * already persisted, so later pipeline stages don't re-call the
     * (paid) vehicle data provider for the same lookup.
     */
    public function toVehicleData(): VehicleData
    {
        $vehicle = $this->vehicle;
        $history = $this->history;

        return new VehicleData(
            registration: $this->registration,
            vin: $vehicle?->vin,
            make: $vehicle?->make,
            model: $vehicle?->model,
            derivative: $vehicle?->derivative,
            year: $vehicle?->year,
            engine: $vehicle?->engine,
            fuel: $vehicle?->fuel,
            transmission: $vehicle?->transmission,
            colour: $vehicle?->colour,
            specification: $vehicle?->specification,
            writeOffCategory: $history?->write_off_category,
            writeOffDate: $history?->write_off_date?->toDateString(),
            financeMarker: (bool) $history?->finance_marker,
            stolenMarker: (bool) $history?->stolen_marker,
            highRiskMarker: (bool) $history?->high_risk_marker,
            scrappedMarker: (bool) $history?->scrapped_marker,
            imported: (bool) $history?->imported,
            exported: (bool) $history?->exported,
            previousKeepers: $history?->previous_keepers,
            plateChanges: $history?->plate_changes,
            mileageAnomaly: (bool) $history?->mileage_anomaly,
            motHistory: $history?->mot_history ?? [],
            keeperHistory: $history?->keeper_history ?? [],
            confidence: $history?->confidence ?? 'medium',
            colourChanges: $history?->colour_changes,
            wasExported: $history?->was_exported,
            vehicleIdentityChecks: $history?->vehicle_identity_checks,
            v5cReissues: $history?->v5c_reissues,
            previousSearches: $history?->previous_searches,
            vrmMatches: $history?->vrm_matches,
            vinMatches: $history?->vin_matches,
            plateChangeHistory: $history?->plate_change_history ?? [],
            damageLocations: $history?->damage_locations ?? [],
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleCheckImage::class)->orderBy('position');
    }

    public function history(): HasOne
    {
        return $this->hasOne(VehicleHistory::class);
    }

    public function valuation(): HasOne
    {
        return $this->hasOne(VehicleValuation::class);
    }

    public function damageAnalysis(): HasOne
    {
        return $this->hasOne(DamageAnalysis::class);
    }

    public function repairEstimate(): HasOne
    {
        return $this->hasOne(RepairEstimate::class);
    }

    public function bidRecommendation(): HasOne
    {
        return $this->hasOne(BidRecommendation::class);
    }

    public function report(): HasOne
    {
        return $this->hasOne(Report::class);
    }

    public function salvageAuctionCheck(): HasOne
    {
        return $this->hasOne(SalvageAuctionCheck::class);
    }

    public function taxCost(): HasOne
    {
        return $this->hasOne(VehicleTaxCost::class);
    }

    public function aiUsages(): HasMany
    {
        return $this->hasMany(AiUsage::class);
    }

    public function listingImport(): BelongsTo
    {
        return $this->belongsTo(ListingImport::class);
    }
}
