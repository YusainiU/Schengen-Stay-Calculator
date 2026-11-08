<?php

namespace Simplydigital\SchengenCalculator\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Simplydigital\SchengenCalculator\DTO\TripResult;

class MaximumStayCalculator
{
    public function __construct(
        private StayCalculator $calculator,
        private int $maximumDays = 90,
        private int $referencePeriod = 180,
    ) {}

    /**
     * Calculate the maximum consecutive stay.
     *
     * $stays must contain historical travel, not
     * the proposed trip.
     */
    public function maximumStay(
        array $stays,
        string $entryDate
    ): TripResult {
        $entry = CarbonImmutable::parse($entryDate)->startOfDay();

        $plannedStays = $stays;
        $daysAllowed = 0;
        $lastAllowedDay = null;

        for ($i = 0; $i < $this->referencePeriod; $i++) {
            $candidate = $entry->addDays($i);

            $result = $this->calculator->calculate(
                $plannedStays,
                $candidate->toDateString()
            );

            // The candidate day itself must also count.
            if ($this->maximumDays < $result['days_used'] + 1) {
                break;
            }

            $daysAllowed++;
            $lastAllowedDay = $candidate;

            // Include this proposed day in subsequent checks.
            $plannedStays[] = [
                'entry' => $candidate->toDateString(),
                'exit' => $candidate->toDateString(),
                'country_code' => 'FR',
            ];
        }

        return new TripResult(
            entryDate: $entry,
            exitDate: null,
            daysAllowed: $daysAllowed,
            compliant: true,
            lastAllowedDay: $lastAllowedDay,
        );
    }

    /**
     * Validate a proposed trip.
     */
    public function checkTrip(
        array $stays,
        string $entryDate,
        string $exitDate
    ): TripResult {
        $entry = CarbonImmutable::parse($entryDate)->startOfDay();
        $exit = CarbonImmutable::parse($exitDate)->startOfDay();

        if ($exit->lessThan($entry)) {
            throw new InvalidArgumentException(
                'Exit date cannot be before entry date.'
            );
        }

        $plannedStays = $stays;
        $dailyBreakdown = [];

        $daysAllowed = 0;
        $lastAllowedDay = null;
        $compliant = true;

        for (
            $date = $entry;
            $date->lessThanOrEqualTo($exit);
            $date = $date->addDay()
        ) {
            $result = $this->calculator->calculate(
                $plannedStays,
                $date->toDateString()
            );

            $used = $result['days_used'] + 1;
            $dayCompliant = $used <= $this->maximumDays;

            $dailyBreakdown[] = [
                'date' => $date->toDateString(),
                'days_used' => $used,
                'days_remaining' => max(
                    0,
                    $this->maximumDays - $used
                ),
                'compliant' => $dayCompliant,
            ];

            if (! $dayCompliant) {
                $compliant = false;
            } elseif ($compliant) {
                $daysAllowed++;
                $lastAllowedDay = $date;
            }

            // Include every proposed day, even if it
            // exceeds the allowance, to simulate actual presence.
            $plannedStays[] = [
                'entry' => $date->toDateString(),
                'exit' => $date->toDateString(),
                'country_code' => 'FR',
            ];
        }

        return new TripResult(
            entryDate: $entry,
            exitDate: $exit,
            daysAllowed: $daysAllowed,
            compliant: $compliant,
            lastAllowedDay: $lastAllowedDay,
            dailyBreakdown: $dailyBreakdown,
        );
    }

    private function validateHistoricalStays(
        array $stays,
        CarbonImmutable $entryDate
    ): void {
        foreach ($stays as $stay) {
            if (
                ! isset($stay['entry']) ||
                ! isset($stay['exit']) ||
                $stay['exit'] === null
            ) {
                throw new InvalidArgumentException(
                    'Trip planning requires completed historical stays.'
                );
            }

            $exit = CarbonImmutable::parse($stay['exit'])
                ->startOfDay();

            if ($exit->greaterThanOrEqualTo($entryDate)) {
                throw new InvalidArgumentException(
                    'Historical stays must end before the proposed arrival date.'
                );
            }
        }
    }
}
