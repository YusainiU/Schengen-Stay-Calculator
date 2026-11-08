<?php

namespace Simplydigital\SchengenCalculator\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class StayNormalizer
{

    private readonly CountryCodeValidator $countryCodeValidator;
    private const ALLOWED_TYPES = [
        'short_stay',
        'residence_permit',
        'long_stay_visa',
    ];

    public function __construct(
        ?CountryCodeValidator $countryCodeValidator = null
    ) {
        $this->countryCodeValidator = $countryCodeValidator
            ?? new CountryCodeValidator();
    }

    public function normalize(array $stays): array
    {
        $normalised = [];

        foreach ($stays as $index => $stay) {
            if (!is_array($stay)) {
                throw new InvalidArgumentException(
                    "Stay at index {$index} must be an array."
                );
            }

            if (!array_key_exists('entry', $stay)) {
                throw new InvalidArgumentException(
                    "Stay at index {$index} is missing its entry date."
                );
            }

            $entry = $this->parseDate(
                $stay['entry'],
                "entry date at index {$index}"
            );

            $exit = null;

            if (($stay['exit'] ?? null) !== null) {
                $exit = $this->parseDate(
                    $stay['exit'],
                    "exit date at index {$index}"
                );

                if ($exit->lt($entry)) {
                    throw new InvalidArgumentException(
                        "Exit date cannot be before entry date at index {$index}."
                    );
                }
            }

            $type = $stay['type'] ?? 'short_stay';

            if (
                !\is_string($type)
                || !\in_array($type, self::ALLOWED_TYPES, true)
            ) {
                throw new InvalidArgumentException(
                    "Invalid stay type at index {$index}."
                );
            }

            $countryCode = $this->normaliseCountryCode(
                $stay['country_code'] ?? null,
                "country_code at index {$index}"
            );

            if ($countryCode === null) {
                throw new InvalidArgumentException(
                    "Stay at index {$index} requires a country_code."
                );
            }

            $permitCountryCode = $this->normaliseCountryCode(
                $stay['permit_country_code'] ?? null,
                "permit_country_code at index {$index}"
            );

            if ($type !== 'short_stay') {
                if ($permitCountryCode === null) {
                    throw new InvalidArgumentException(
                        "Stay at index {$index} requires both country_code "
                        . 'and permit_country_code for permit-based classification.'
                    );
                }
            }

            $normalised[] = [
                'entry' => $entry,
                'exit' => $exit,
                'type' => $type,
                'country_code' => $countryCode,
                'permit_country_code' => $permitCountryCode,
            ];
        }

        usort(
            $normalised,
            fn(array $a, array $b): int =>
                $a['entry']->getTimestamp()
                <=> $b['entry']->getTimestamp()
        );

        return $normalised;
    }

    private function normaliseCountryCode(
        mixed $value,
        string $field
    ): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException(
                "The {$field} must be a string."
            );
        }

        return $this->countryCodeValidator->normalize($value);
    }
    public function parseDate(
        mixed $value,
        string $field = 'date'
    ): CarbonImmutable {
        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        if (
            !is_string($value)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)
        ) {
            throw new InvalidArgumentException(
                "The {$field} must use YYYY-MM-DD format."
            );
        }

        $date = CarbonImmutable::createFromFormat(
            '!Y-m-d',
            $value,
            'UTC'
        );

        if (
            $date === false
            || $date->format('Y-m-d') !== $value
        ) {
            throw new InvalidArgumentException(
                "The {$field} is not a valid calendar date."
            );
        }

        return $date;
    }
}