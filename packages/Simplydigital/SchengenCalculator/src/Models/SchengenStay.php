<?php

namespace Simplydigital\SchengenCalculator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SchengenStay extends Model
{
    protected $table = 'schengen_stays';

    protected $fillable = [
        'entry_date',
        'exit_date',
        'country_code',
        'type',
        'permit_country_code',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'immutable_date',
            'exit_date' => 'immutable_date',
        ];
    }

    public function traveller(): MorphTo
    {
        return $this->morphTo();
    }
}