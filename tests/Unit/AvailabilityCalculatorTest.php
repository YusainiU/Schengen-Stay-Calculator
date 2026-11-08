<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simplydigital\SchengenCalculator\Services\AvailabilityCalculator;
use Simplydigital\SchengenCalculator\Services\StayCalculator;
use Simplydigital\SchengenCalculator\Services\StayNormalizer;

class AvailabilityCalculatorTest extends TestCase
{
    private AvailabilityCalculator $calculator;

    protected function setUp(): void
    {
        $stayCalculator = new StayCalculator(
            new StayNormalizer
        );

        $this->calculator = new AvailabilityCalculator(
            $stayCalculator
        );
    }

    private function previousStay(): array
    {
        return [
            [
                'entry' => '2026-01-01',
                'exit' => '2026-03-31',
                'country_code' => 'FR',
            ],
        ];
    }

    public function test_it_finds_the_next_available_date(): void
    {
        $result = $this->calculator->nextAvailableDate(
            $this->previousStay(),
            '2026-06-29'
        );        
        $this->assertEquals('2026-06-30', $result);
    }

    public function test_it_calculates_available_days(): void
    {
        $result = $this->calculator->availableDaysOn(
            $this->previousStay(),
            '2026-06-30'
        );

        $this->assertEquals(89, $result['days_used']);
        $this->assertEquals(1, $result['days_available']);
    }

    public function test_it_generates_an_availability_calendar(): void
    {
        $result = $this->calculator->availabilityCalendar(
            $this->previousStay(),
            '2026-06-29',
            '2026-07-02'
        );

        $this->assertCount(4, $result);

        $this->assertEquals(
            0,
            $result[0]['days_available']
        );

        $this->assertEquals(
            1,
            $result[1]['days_available']
        );

        $this->assertEquals(
            3,
            $result[3]['days_available']
        );
    }

    public function test_it_calculates_days_released(): void
    {
        $result = $this->calculator->daysReleased(
            $this->previousStay(),
            '2026-06-29',
            '2026-06-30'
        );

        $this->assertEquals(90, $result['days_used_before']);
        $this->assertEquals(89, $result['days_used_after']);
        $this->assertEquals(1, $result['days_released']);
    }

    public function test_it_rejects_invalid_calendar_ranges(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->availabilityCalendar(
            [],
            '2026-07-01',
            '2026-06-01'
        );
    }
}
