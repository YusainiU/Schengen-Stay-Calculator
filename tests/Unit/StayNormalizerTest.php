<?php

namespace Tests\Unit;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Simplydigital\SchengenCalculator\Services\StayNormalizer;

class StayNormalizerTest extends TestCase
{
    private StayNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new StayNormalizer;
    }

    public function test_it_accepts_valid_iso_dates(): void
    {
        $stays = $this->normalizer->normalize([
            [
                'entry' => '2026-06-01',
                'exit' => '2026-06-10',
                'country_code' => 'FR',
            ],
        ]);

        $this->assertSame('2026-06-01', $stays[0]['entry']->format('Y-m-d'));
        $this->assertSame('2026-06-10', $stays[0]['exit']->format('Y-m-d'));
    }

    public function test_it_rejects_impossible_calendar_dates(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->normalizer->normalize([
            [
                'entry' => '2026-02-30',
                'exit' => '2026-03-05',
                'country_code' => 'FR',
            ],
        ]);
    }

    public function test_it_rejects_ambiguous_date_formats(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->normalizer->normalize([
            [
                'entry' => '01/02/2026',
                'exit' => '10/02/2026',
                'country_code' => 'FR',
            ],
        ]);
    }

    public function test_it_rejects_exit_before_entry(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->normalizer->normalize([
            [
                'entry' => '2026-06-10',
                'exit' => '2026-06-01',
                'country_code' => 'FR',
            ],
        ]);
    }

    public function test_it_accepts_an_open_ended_stay(): void
    {
        $stays = $this->normalizer->normalize([
            [
                'entry' => '2026-06-01',
                'exit' => null,
                'country_code' => 'FR',
            ],
        ]);

        $this->assertNull($stays[0]['exit']);
    }

    public function test_it_accepts_date_time_objects(): void
    {
        $stays = $this->normalizer->normalize([
            [
                'entry' => new CarbonImmutable('2026-06-01 14:30:00'),
                'exit' => new CarbonImmutable('2026-06-10 09:00:00'),
                'country_code' => 'FR',
            ],
        ]);

        $this->assertSame('2026-06-01', $stays[0]['entry']->format('Y-m-d'));
        $this->assertSame('2026-06-10', $stays[0]['exit']->format('Y-m-d'));
    }
}
