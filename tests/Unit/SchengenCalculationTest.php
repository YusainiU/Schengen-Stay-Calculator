<?php

namespace Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Simplydigital\SchengenCalculator\Services\CountryCodeValidator;
use Simplydigital\SchengenCalculator\Services\SchengenCountryRegistry;
use Simplydigital\SchengenCalculator\Services\StayCalculator;
use Simplydigital\SchengenCalculator\Services\StayNormalizer;

class SchengenCalculationTest extends TestCase
{
    private StayCalculator $calculator;

    protected function setUp(): void
    {
        $validator = new CountryCodeValidator();

        $normalizer = new StayNormalizer($validator);

        $registry = new SchengenCountryRegistry();

        $this->calculator = new StayCalculator(
            $normalizer,
            90,
            180,
            $registry
        );
    }

    public function test_it_counts_a_trip_in_one_schengen_country(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-10',
                'country_code' => 'FR',
            ],
        ], '2026-01-10');

        $this->assertSame(10, $result['days_used']);
        $this->assertSame(80, $result['days_remaining']);
        $this->assertTrue($result['compliant']);
    }

    public function test_it_counts_travel_across_multiple_schengen_countries(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-05',
                'country_code' => 'FR',
            ],
            [
                'entry' => '2026-01-06',
                'exit' => '2026-01-10',
                'country_code' => 'DE',
            ],
        ], '2026-01-10');

        // Changing Schengen countries does not reset the allowance.
        $this->assertSame(10, $result['days_used']);
        $this->assertSame(80, $result['days_remaining']);
    }

    public function test_it_does_not_count_days_in_ireland(): void
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

    public function test_it_counts_schengen_days_but_not_non_schengen_days(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-05',
                'country_code' => 'FR',
            ],
            [
                'entry' => '2026-01-06',
                'exit' => '2026-01-10',
                'country_code' => 'IE',
            ],
        ], '2026-01-10');

        $this->assertSame(5, $result['days_used']);
    }

    public function test_it_does_not_double_count_overlapping_records(): void
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
                'country_code' => 'DE',
            ],
        ], '2026-01-15');

        $this->assertSame(15, $result['days_used']);
    }

    public function test_it_counts_entry_and_exit_days(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-01',
                'country_code' => 'FR',
            ],
        ], '2026-01-01');

        $this->assertSame(1, $result['days_used']);
    }

    public function test_it_counts_exactly_90_days_as_compliant(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-03-31',
                'country_code' => 'FR',
            ],
        ], '2026-03-31');

        $this->assertSame(90, $result['days_used']);
        $this->assertSame(0, $result['days_remaining']);
        $this->assertTrue($result['compliant']);
    }

    public function test_it_detects_a_91st_day(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-04-01',
                'country_code' => 'FR',
            ],
        ], '2026-04-01');

        $this->assertSame(91, $result['days_used']);
        $this->assertSame(0, $result['days_remaining']);
        $this->assertFalse($result['compliant']);
    }

    public function test_it_applies_the_rolling_180_day_boundary(): void
    {
        $stays = [
            [
                'entry' => '2026-01-01',
                'exit' => '2026-03-31',
                'country_code' => 'FR',
            ],
        ];

        $onJune29 = $this->calculator->calculate(
            $stays,
            '2026-06-29'
        );

        $onJune30 = $this->calculator->calculate(
            $stays,
            '2026-06-30'
        );

        $this->assertSame(90, $onJune29['days_used']);
        $this->assertSame('2026-01-01', $onJune29['period_start']);

        // 1 January has fallen outside the rolling window.
        $this->assertSame(89, $onJune30['days_used']);
        $this->assertSame('2026-01-02', $onJune30['period_start']);
        $this->assertSame(1, $onJune30['days_remaining']);
    }

    public function test_it_applies_bulgarias_schengen_start_date(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2024-03-25',
                'exit' => '2024-04-05',
                'country_code' => 'BG',
            ],
        ], '2024-04-05');

        // 31 March through 5 April inclusive.
        $this->assertSame(6, $result['days_used']);
    }

    public function test_it_applies_croatias_schengen_start_date(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2022-12-29',
                'exit' => '2023-01-03',
                'country_code' => 'HR',
            ],
        ], '2023-01-03');

        // 1, 2 and 3 January count.
        $this->assertSame(3, $result['days_used']);
    }

    public function test_it_excludes_a_permit_period_in_its_issuing_country(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-30',
                'country_code' => 'FR',
                'type' => 'residence_permit',
                'permit_country_code' => 'FR',
            ],
        ], '2026-01-30');

        $this->assertSame(0, $result['days_used']);
    }

    public function test_it_counts_a_visit_outside_the_permit_issuing_country(): void
    {
        $result = $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-10',
                'country_code' => 'DE',
                'type' => 'residence_permit',
                'permit_country_code' => 'FR',
            ],
        ], '2026-01-10');

        $this->assertSame(10, $result['days_used']);
    }

    public function test_it_rejects_an_invalid_country_code(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-10',
                'country_code' => 'ZZ',
            ],
        ], '2026-01-10');
    }

    public function test_it_rejects_a_missing_country_code(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculate([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-10',
            ],
        ], '2026-01-10');
    }
}