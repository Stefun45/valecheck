<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One row per genuine free vehicle-preview attempt (homepage widget or the
 * start-check page), recorded directly at the point of the user action —
 * deliberately not inferred from ProviderLookupLog, since the 30-minute
 * MOT/tax cache means a repeat lookup of the same plate creates no log row
 * there, which would undercount real usage of the feature.
 */
#[Fillable(['source'])]
class FreeLookupLog extends Model
{
    public const UPDATED_AT = null;

    public const SOURCE_HOMEPAGE = 'homepage';

    public const SOURCE_START_CHECK = 'start_check';
}
