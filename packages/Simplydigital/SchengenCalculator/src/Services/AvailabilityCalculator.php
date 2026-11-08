<?php

namespace Simplydigital\SchengenCalculator\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class AvailabilityCalculator
{
    public function __construct(
        private StayCalculator $calculator,
        private int $maximumDays = 90,
        private int $referencePeriod = 180,
    ) {}

    /**
     * Get available days on a particular date.
     */
    public function availableDaysOn(
        array $stays,
        string $date
    ): array {
        $result = $this->calculator->calculate(
            $stays,
            $date
        );

        return [
            'date' => $result['date'],
            'days_used' => $result['days_used'],
            'days_available' => max(
                0,
                $this->maximumDays - $result['days_used']
            ),
            'compliant' => $result['compliant'],
            'period_start' => $result['period_start'],
            'period_end' => $result['period_end'],
        ];
    }

    /**
     * Find the first date with at least one available day.
     *
     * The search includes the supplied start date.
     */
    public function nextAvailableDate(
        array $stays,
        string $fromDate,
        int $searchDays = 366
    ): ?string {
        if ($searchDays < 1) {
            throw new InvalidArgumentException(
                'Search days must be at least 1.'
            );
        }

        $start = CarbonImmutable::parse($fromDate)->startOfDay();

        for ($i = 0; $i < $searchDays; $i++) {
            $date = $start->addDays($i)->toDateString();

            $result = $this->availableDaysOn(
                $stays,
                $date
            );

            if ($result['days_available'] > 0) {
                return $date;
            }
        }

        return null;
    }

    /**
     * Build a daily availability calendar.
     */
    public function availabilityCalendar(
        array $stays,
        string $startDate,
        string $endDate
    ): array {
        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->startOfDay();

        if ($end->lessThan($start)) {
            throw new InvalidArgumentException(
                'End date cannot be before start date.'
            );
        }

        $days = $start->diffInDays($end);

        if ($days > 365) {
            throw new InvalidArgumentException(
                'Calendar range cannot exceed 366 days.'
            );
        }

        $calendar = [];

        for ($i = 0; $i <= $days; $i++) {
            $date = $start->addDays($i)->toDateString();

            $calendar[] = $this->availableDaysOn(
                $stays,
                $date
            );
        }

        return $calendar;
    }

    /**
     * Calculate how many used days have been released
     * between two check dates.
     */
    public function daysReleased(
        array $stays,
        string $fromDate,
        string $toDate
    ): array {
        $from = CarbonImmutable::parse($fromDate)->startOfDay();
        $to = CarbonImmutable::parse($toDate)->startOfDay();

        if ($to->lessThan($from)) {
            throw new InvalidArgumentException(
                'To date cannot be before from date.'
            );
        }

        $fromResult = $this->availableDaysOn(
            $stays,
            $from->toDateString()
        );

        $toResult = $this->availableDaysOn(
            $stays,
            $to->toDateString()
        );

        $released = max(
            0,
            $fromResult['days_used'] - $toResult['days_used']
        );

        return [
            'from_date' => $from->toDateString(),
            'to_date' => $to->toDateString(),
            'days_used_before' => $fromResult['days_used'],
            'days_used_after' => $toResult['days_used'],
            'days_released' => $released,
        ];
    }
}
