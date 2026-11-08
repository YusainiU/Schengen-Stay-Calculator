<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simplydigital\SchengenCalculator\Services\StayCalculator;
use Simplydigital\SchengenCalculator\Services\StayNormalizer;
use Simplydigital\SchengenCalculator\Services\SchengenCountryRegistry;

class HistoricalMembershipCalculationTest extends TestCase
{
    private StayCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new StayCalculator(
            new StayNormalizer(),
            90,
            180,
            new SchengenCountryRegistry()
        );
    }

    public function test_bulgaria_days_before_accession_are_not_counted(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2024-03-25',
                'exit' => '2024-03-30',
                'country_code' => 'BG',
            ],
        ], '2024-03-30');

        $this->assertSame(0, $result['days_used']);
    }

    public function test_bulgaria_days_after_accession_are_counted(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2024-03-25',
                'exit' => '2024-04-05',
                'country_code' => 'BG',
            ],
        ], '2024-04-05');

        $this->assertSame(6, $result['days_used']);
    }

    public function test_non_schengen_country_days_are_not_counted(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-10',
                'country_code' => 'IE',
            ],
        ], '2026-01-10');

        $this->assertSame(0, $result['days_used']);
    }

    public function test_schengen_country_days_are_counted(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-10',
                'country_code' => 'PT',
            ],
        ], '2026-01-10');

        $this->assertSame(10, $result['days_used']);
    }
}
