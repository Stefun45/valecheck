<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A manually-set "count from here" point for a dashboard figure the site
 * owner wants to zero out on their own schedule, rather than one tied to a
 * calendar boundary (e.g. "revenue since I last checked", not strictly
 * "revenue today"). One row per tracked key.
 */
#[Fillable(['key', 'reset_at'])]
class AdminMetricReset extends Model
{
    protected function casts(): array
    {
        return [
            'reset_at' => 'datetime',
        ];
    }

    /**
     * The point in time to count from for this key — created and set to
     * now() the first time it's asked for, so a figure starts a clean
     * count from when it was first introduced rather than showing
     * everything that ever happened before this feature existed.
     */
    public static function pointFor(string $key): Carbon
    {
        return self::firstOrCreate(['key' => $key], ['reset_at' => now()])->reset_at;
    }

    public static function reset(string $key): void
    {
        self::updateOrCreate(['key' => $key], ['reset_at' => now()]);
    }
}
