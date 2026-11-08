<?php

namespace Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Simplydigital\SchengenCalculator\Services\StayNormalizer;

class StayTypeTest extends TestCase
{
    private StayNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new StayNormalizer;
    }

    public function test_missing_type_defaults_to_short_stay(): void
    {
        $stays = $this->normalizer->normalize([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-10',
                'country_code' => 'FR',
            ],
        ]);

        $this->assertSame('short_stay', $stays[0]['type']);
    }

    public function test_it_accepts_supported_stay_types(): void
    {
        $stays = $this->normalizer->normalize([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-10',
                'country_code' => 'FR',
                'type' => 'short_stay',
            ],
            [
                'entry' => '2026-02-01',
                'exit' => '2026-02-10',
                'country_code' => 'FR',
                'permit_country_code' => 'FR',
                'type' => 'residence_permit',
            ],
            [
                'entry' => '2026-03-01',
                'exit' => '2026-03-10',
                'country_code' => 'FR',
                'permit_country_code' => 'FR',
                'type' => 'long_stay_visa',
            ],
        ]);

        $this->assertCount(3, $stays);
    }

    public function test_it_rejects_unknown_stay_types(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->normalizer->normalize([
            [
                'entry' => '2026-01-01',
                'exit' => '2026-01-10',
                'country_code' => 'FR',
                'type' => 'holiday',
            ],
        ]);
    }
}
