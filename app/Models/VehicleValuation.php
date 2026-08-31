<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vehicle_check_id', 'clean_value', 'trade_value', 'retail_value', 'private_value',
    'salvage_adjusted_value', 'write_off_category_applied', 'discount_applied',
    'comparables', 'confidence', 'valuation_source',
    'dealer_forecourt', 'trade_average', 'trade_poor', 'private_average', 'part_exchange', 'auction_value', 'list_price',
    'category_adjusted_value_low', 'category_adjusted_value_high',
    'salvage_auction_bid_low', 'salvage_auction_bid_average', 'salvage_auction_bid_high',
])]
class VehicleValuation extends Model
{
    protected function casts(): array
    {
        return [
            'clean_value' => 'decimal:2',
            'trade_value' => 'decimal:2',
            'retail_value' => 'decimal:2',
            'private_value' => 'decimal:2',
            'salvage_adjusted_value' => 'decimal:2',
            'discount_applied' => 'decimal:4',
            'comparables' => 'array',
            'dealer_forecourt' => 'decimal:2',
            'trade_average' => 'decimal:2',
            'trade_poor' => 'decimal:2',
            'private_average' => 'decimal:2',
            'part_exchange' => 'decimal:2',
            'auction_value' => 'decimal:2',
            'list_price' => 'decimal:2',
            'category_adjusted_value_low' => 'decimal:2',
            'category_adjusted_value_high' => 'decimal:2',
            'salvage_auction_bid_low' => 'decimal:2',
            'salvage_auction_bid_average' => 'decimal:2',
            'salvage_auction_bid_high' => 'decimal:2',
        ];
    }

    public function vehicleCheck(): BelongsTo
    {
        return $this->belongsTo(VehicleCheck::class);
    }
}
