<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'gross'])]
class ProductPrice extends Model
{
    protected function casts(): array
    {
        return [
            'gross' => 'decimal:2',
        ];
    }
}
