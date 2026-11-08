<?php

namespace Simplydigital\SchengenCalculator\Services;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class DatabaseStayRepository
{
    public function getFor(Model $traveller): array
    {
        if (! method_exists($traveller, 'schengenStays')) {
            throw new InvalidArgumentException(
                'The traveller model must use the HasSchengenStays trait.'
            );
        }

        return $traveller->schengenStays()
            ->orderBy('entry_date')
            ->get()
            ->map(fn ($stay) => [
                'entry' => $stay->entry_date->format('Y-m-d'),
                'exit' => $stay->exit_date?->format('Y-m-d'),
                'type' => $stay->type,
                'country_code' => $stay->country_code,
                'permit_country_code' => $stay->permit_country_code,
            ])
            ->all();
    }
}