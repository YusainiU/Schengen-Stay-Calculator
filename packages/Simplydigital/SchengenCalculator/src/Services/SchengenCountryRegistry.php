<?php

namespace Simplydigital\SchengenCalculator\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class SchengenCountryRegistry
{
    /**
     * Dates on which the Schengen rules became applicable
     * for short-stay calculations in each country.
     *
     * Dates use ISO 8601 YYYY-MM-DD format.
     */
    private const MEMBERSHIP_DATES = [
        // Original implementation group.
        'BE' => '1995-03-26',
        'FR' => '1995-03-26',
        'DE' => '1995-03-26',
        'LU' => '1995-03-26',
        'NL' => '1995-03-26',
        'PT' => '1995-03-26',
        'ES' => '1995-03-26',

        // 1997.
        'AT' => '1997-10-26',
        'IT' => '1997-10-26',

        // 2000.
        'GR' => '2000-03-26',

        // 2001.
        'DK' => '2001-03-25',
        'FI' => '2001-03-25',
        'SE' => '2001-03-25',
        'IS' => '2001-03-25',
        'NO' => '2001-03-25',

        // 2007.
        'CZ' => '2007-12-21',
        'EE' => '2007-12-21',
        'HU' => '2007-12-21',
        'LV' => '2007-12-21',
        'LT' => '2007-12-21',
        'MT' => '2007-12-21',
        'PL' => '2007-12-21',
        'SK' => '2007-12-21',
        'SI' => '2007-12-21',

        // 2008.
        'CH' => '2008-12-12',

        // 2011.
        'LI' => '2011-12-19',

        // 2023.
        'HR' => '2023-01-01',

        // 2024: Bulgaria and Romania.
        'BG' => '2024-03-31',
        'RO' => '2024-03-31',
    ];

    /**
     * Check whether a country was in Schengen on a given date.
     */
    public function isSchengen(
        string $countryCode,
        string $date
    ): bool {
        $countryCode = strtoupper(trim($countryCode));

        if (! preg_match('/^[A-Z]{2}$/', $countryCode)) {
            throw new InvalidArgumentException(
                'Country code must be a two-letter ISO country code.'
            );
        }

        $checkDate = $this->parseDate($date);

        $membershipDate = self::MEMBERSHIP_DATES[$countryCode] ?? null;

        if ($membershipDate === null) {
            return false;
        }

        $joined = CarbonImmutable::parse($membershipDate, 'UTC');

        return $checkDate->gte($joined);
    }

    /**
     * Return all Schengen countries on a particular date.
     */
    public function allOnDate(string $date): array
    {
        $countries = [];

        foreach (self::MEMBERSHIP_DATES as $code => $membershipDate) {
            if ($this->isSchengen($code, $date)) {
                $countries[] = $code;
            }
        }

        sort($countries);

        return $countries;
    }

    /**
     * Return the currently recognised Schengen countries.
     */
    public function all(): array
    {
        return $this->allOnDate(
            CarbonImmutable::now('UTC')->format('Y-m-d')
        );
    }

    private function parseDate(string $date): CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new InvalidArgumentException(
                'Date must use YYYY-MM-DD format.'
            );
        }

        $parsed = CarbonImmutable::createFromFormat(
            '!Y-m-d',
            $date,
            'UTC'
        );

        if (
            $parsed === false
            || $parsed->format('Y-m-d') !== $date
        ) {
            throw new InvalidArgumentException(
                'Invalid calendar date.'
            );
        }

        return $parsed;
    }
}