<?php

namespace Simplydigital\SchengenCalculator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array calculate(array $stays, string $date)
 * @method static int remainingDays(array $stays, string $date)
 * @method static \Simplydigital\SchengenCalculator\DTO\TripResult maximumStay(array $stays, string $entryDate)
 * @method static \Simplydigital\SchengenCalculator\DTO\TripResult checkTrip(array $stays, string $entryDate, string $exitDate)
 */
class Schengen extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'schengen.calculator';
    }
}
