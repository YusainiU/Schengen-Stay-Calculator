<?php

namespace Simplydigital\SchengenCalculator\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Simplydigital\SchengenCalculator\Models\SchengenStay;

trait HasSchengenStays
{
    public function schengenStays(): MorphMany
    {
        return $this->morphMany(SchengenStay::class, 'traveller');
    }
}
