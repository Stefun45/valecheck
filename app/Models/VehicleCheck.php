<?php

namespace App\Models;

use App\DataTransferObjects\VehicleData;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id', 'vehicle_id', 'type', 'status', 'stage', 'funding_source',
    'payment_id', 'credit_transaction_id', 'registration', 'mileage',
    'listing_url', 'auction_name', 'current_bid', 'asking_price',
    'listing_description', 'listing_import_id', 'listing_data_sources', 'discount_code',
    'failure_reason', 'started_at', 'completed_at', 'expires_at', 'purged_at',
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
        ];
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
            scrappedMarker: (bool) $history?->scrapped_marker,
            imported: (bool) $history?->imported,
            exported: (bool) $history?->exported,
            previousKeepers: $history?->previous_keepers,
            plateChanges: $history?->plate_changes,
            mileageAnomaly: (bool) $history?->mileage_anomaly,
            motHistory: $history?->mot_history ?? [],
            keeperHistory: $history?->keeper_history ?? [],
            confidence: $history?->confidence ?? 'medium',
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

    public function aiUsages(): HasMany
    {
        return $this->hasMany(AiUsage::class);
    }

    public function listingImport(): BelongsTo
    {
        return $this->belongsTo(ListingImport::class);
    }
}
