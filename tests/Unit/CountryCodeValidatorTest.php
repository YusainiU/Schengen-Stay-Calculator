<?php

namespace Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Simplydigital\SchengenCalculator\Services\CountryCodeValidator;

class CountryCodeValidatorTest extends TestCase
{
    private CountryCodeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CountryCodeValidator();
    }

    public function test_it_accepts_valid_country_codes(): void
    {
        $this->assertSame('FR', $this->validator->normalize('FR'));
        $this->assertSame('GB', $this->validator->normalize('GB'));
        $this->assertSame('PT', $this->validator->normalize('PT'));
    }

    public function test_it_normalises_lowercase_codes(): void
    {
        $this->assertSame('FR', $this->validator->normalize('fr'));
    }

    public function test_it_rejects_unknown_codes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->normalize('ZZ');
    }

    public function test_it_rejects_three_letter_codes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->normalize('FRA');
    }

    public function test_it_rejects_empty_codes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->normalize('');
    }

    public function test_is_valid_returns_false_for_unknown_codes(): void
    {
        $this->assertFalse($this->validator->isValid('ZZ'));
        $this->assertTrue($this->validator->isValid('FR'));
    }
}