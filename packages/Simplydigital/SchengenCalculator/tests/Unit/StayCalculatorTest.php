<?php

namespace Simplydigital\SchengenCalculator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simplydigital\SchengenCalculator\Services\StayCalculator;
use Simplydigital\SchengenCalculator\Services\StayNormalizer;

class StayCalculatorTest extends TestCase
{
    private StayCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new StayCalculator(
            new StayNormalizer
        );
    }

    public function test_it_calculates_used_days(): void
    {
        $stays = [
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-15',
                'country_code' => 'FR',
            ],
            [
                'entry' => '2026-03-01',
                'exit' => '2026-03-20',
                'country_code' => 'FR',
            ],
        ];

        $result = $this->calculator->calculate(
            $stays,
            '2026-04-01'
        );

        $this->assertEquals(35, $result['days_used']);
        $this->assertEquals(55, $result['days_remaining']);
        $this->assertTrue($result['compliant']);
    }

    public function test_it_counts_arrival_and_departure_days(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-01',
                'country_code' => 'FR',
            ],
        ], '2026-01-01');

        $this->assertEquals(1, $result['days_used']);
    }

    public function test_it_does_not_double_count_overlapping_stays(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-10',
                'country_code' => 'FR',
            ],
            [
                'entry' => '2026-01-05',
                'exit' => '2026-01-15',
                'country_code' => 'FR',
            ],
        ], '2026-01-15');

        $this->assertEquals(15, $result['days_used']);
    }

    public function test_it_ignores_stays_outside_the_180_day_window(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2025-01-01',
                'exit' => '2025-01-10',
                'country_code' => 'FR',
            ],
        ], '2026-01-01');

        $this->assertEquals(0, $result['days_used']);
        $this->assertEquals(90, $result['days_remaining']);
    }
}
