<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simplydigital\SchengenCalculator\Services\StayCalculator;
use Simplydigital\SchengenCalculator\Services\StayNormalizer;

class CountryExemptionTest extends TestCase
{
    private StayCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new StayCalculator(
            new StayNormalizer(),
            90,
            180
        );
    }

    public function test_residence_permit_days_in_issuing_country_are_excluded(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-30',
                'type' => 'residence_permit',
                'country_code' => 'FR',
                'permit_country_code' => 'FR',
            ],
        ], '2026-01-30');

        $this->assertSame(0, $result['days_used']);
    }

    public function test_visits_to_other_schengen_countries_are_counted(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-30',
                'type' => 'residence_permit',
                'country_code' => 'DE',
                'permit_country_code' => 'FR',
            ],
        ], '2026-01-30');

        $this->assertSame(30, $result['days_used']);
    }

    public function test_ordinary_short_stays_are_counted(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-10',
                'type' => 'short_stay',
                'country_code' => 'ES',
            ],
        ], '2026-01-10');

        $this->assertSame(10, $result['days_used']);
    }

    public function test_country_codes_are_case_insensitive(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-10',
                'type' => 'long_stay_visa',
                'country_code' => 'fr',
                'permit_country_code' => 'FR',
            ],
        ], '2026-01-10');

        $this->assertSame(0, $result['days_used']);
    }
}
