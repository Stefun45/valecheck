<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vehicle_check_id', 'expected_resale_value', 'total_repair_cost', 'auction_fees',
    'transport_cost', 'service_mot_allowance', 'contingency', 'required_margin',
    'maximum_acquisition_price', 'recommended_bid', 'absolute_maximum',
    'deal_score', 'recommendation', 'score_explanation',
])]
class BidRecommendation extends Model
{
    public const RECOMMENDATION_BUY = 'buy';

    public const RECOMMENDATION_MAYBE = 'maybe';

    public const RECOMMENDATION_WALK_AWAY = 'walk_away';

    protected function casts(): array
    {
        return [
            'expected_resale_value' => 'decimal:2',
            'total_repair_cost' => 'decimal:2',
            'auction_fees' => 'decimal:2',
            'transport_cost' => 'decimal:2',
            'service_mot_allowance' => 'decimal:2',
            'contingency' => 'decimal:2',
            'required_margin' => 'decimal:2',
            'maximum_acquisition_price' => 'decimal:2',
            'recommended_bid' => 'decimal:2',
            'absolute_maximum' => 'decimal:2',
        ];
    }

    public function vehicleCheck(): BelongsTo
    {
        return $this->belongsTo(VehicleCheck::class);
    }
}
