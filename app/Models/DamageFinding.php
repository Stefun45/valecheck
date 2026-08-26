<?php

namespace App\Models;

use App\DataTransferObjects\DamageFindingData;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['damage_analysis_id', 'component', 'condition', 'severity', 'recommended_action', 'confidence', 'explanation'])]
class DamageFinding extends Model
{
    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:2',
        ];
    }

    public function isDamaged(): bool
    {
        return $this->condition === 'damaged' || $this->condition === 'missing';
    }

    public function toData(): DamageFindingData
    {
        return new DamageFindingData(
            component: $this->component,
            condition: $this->condition,
            severity: $this->severity,
            recommendedAction: $this->recommended_action,
            confidence: (float) $this->confidence,
            explanation: (string) $this->explanation,
        );
    }

    public function damageAnalysis(): BelongsTo
    {
        return $this->belongsTo(DamageAnalysis::class);
    }
}
