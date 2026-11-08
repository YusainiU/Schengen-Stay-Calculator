<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simplydigital\SchengenCalculator\Services\MaximumStayCalculator;
use Simplydigital\SchengenCalculator\Services\StayCalculator;
use Simplydigital\SchengenCalculator\Services\StayNormalizer;

class MaximumStayCalculatorTest extends TestCase
{
    private MaximumStayCalculator $calculator;

    protected function setUp(): void
    {
        $stayCalculator = new StayCalculator(
            new StayNormalizer
        );

        $this->calculator = new MaximumStayCalculator(
            $stayCalculator
        );
    }

    public function test_maximum_stay_without_previous_travel(): void
    {
        $result = $this->calculator->maximumStay(
            [],
            '2026-01-01'
        );

        $this->assertEquals(90, $result->daysAllowed);

        $this->assertEquals(
            '2026-03-31',
            $result->lastAllowedDay->toDateString()
        );
    }

    public function test_it_accounts_for_previous_travel(): void
    {
        $stays = [
            [
                'entry' => '2026-01-01',
                'exit' => '2026-03-26',
                'country_code' => 'FR',
            ],
        ];

        $result = $this->calculator->maximumStay(
            $stays,
            '2026-04-01'
        );

        $this->assertEquals(5, $result->daysAllowed);
    }

    public function test_it_rejects_an_overstaying_trip(): void
    {
        $stays = [
            [
                'entry' => '2026-01-01',
                'exit' => '2026-03-26',
                'country_code' => 'FR',
            ],
        ];

        $result = $this->calculator->checkTrip(
            $stays,
            '2026-04-01',
            '2026-04-06'
        );

        $this->assertFalse($result->compliant);

        $this->assertEquals(5, $result->daysAllowed);

        $this->assertEquals(
            6,
            count($result->dailyBreakdown)
        );
    }

    public function test_it_accepts_a_compliant_trip(): void
    {
        $result = $this->calculator->checkTrip(
            [],
            '2026-01-01',
            '2026-03-31'
        );

        $this->assertTrue($result->compliant);

        $this->assertEquals(90, $result->daysAllowed);
    }
}
