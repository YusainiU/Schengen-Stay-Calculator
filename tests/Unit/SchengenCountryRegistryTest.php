<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simplydigital\SchengenCalculator\Services\SchengenCountryRegistry;

class SchengenCountryRegistryTest extends TestCase
{
    public function test_it_recognises_schengen_countries(): void
    {
        $registry = new SchengenCountryRegistry;

        $this->assertTrue($registry->isSchengen('FR', '2026-01-01'));
        $this->assertTrue($registry->isSchengen('CH', '2026-01-01'));
        $this->assertTrue($registry->isSchengen('NO', '2026-01-01'));
    }

    public function test_it_rejects_non_schengen_countries(): void
    {
        $registry = new SchengenCountryRegistry;

        $this->assertFalse($registry->isSchengen('GB', '2026-01-01'));
        $this->assertFalse($registry->isSchengen('IE', '2026-01-01'));
        $this->assertFalse($registry->isSchengen('CY', '2026-01-01'));
    }

    public function test_it_is_case_insensitive(): void
    {
        $registry = new SchengenCountryRegistry;

        $this->assertTrue($registry->isSchengen('fr', '2026-01-01'));
    }

    public function test_it_contains_29_countries(): void
    {
        $registry = new SchengenCountryRegistry;

        $this->assertCount(29, $registry->all());
    }
}
