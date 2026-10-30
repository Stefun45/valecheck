<?php

namespace App\Support;

/**
 * Time-boxed seasonal theming (e.g. a Halloween skin for a couple of days a
 * year). Bounds live in config so a season turns itself off automatically
 * once the clock passes its end - no deploy needed to revert.
 */
class SeasonalTheme
{
    public static function halloweenActive(): bool
    {
        return now()->between(
            config('valecheck.seasonal_theme.halloween.starts_at'),
            config('valecheck.seasonal_theme.halloween.ends_at'),
        );
    }
}
