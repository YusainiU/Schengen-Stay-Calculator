<?php

namespace Simplydigital\SchengenCalculator\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class StayCalculator
{
    private readonly SchengenCountryRegistry $countryRegistry;

    public function __construct(
        private readonly StayNormalizer $normalizer,
        private readonly int $maximumDays = 90,
        private readonly int $referencePeriod = 180,
        ?SchengenCountryRegistry $countryRegistry = null,
    ) {
        if ($this->maximumDays < 1) {
            throw new InvalidArgumentException(
                'Maximum days must be at least 1.'
            );
        }

        if ($this->referencePeriod < 1) {
            throw new InvalidArgumentException(
                'Reference period must be at least 1.'
            );
        }

        if ($this->maximumDays > $this->referencePeriod) {
            throw new InvalidArgumentException(
                'Maximum days cannot exceed the reference period.'
            );
        }

        $this->countryRegistry = $countryRegistry
            ?? new SchengenCountryRegistry;
    }

    /**
     * Calculate Schengen days used on a particular date.
     *
     * Each stay must contain:
     * [
     *     'entry' => '2026-01-01',
     *     'exit' => '2026-01-10',
     *     'country_code' => 'FR',
     * ]
     *
     * An exit date of null represents an ongoing stay.
     */
    public function calculate(array $stays, string $date): array
    {
        $checkDate = $this->normalizer->parseDate(
            $date,
            'calculation date'
        );

        $normalisedStays = $this->normalizer->normalize($stays);

        $periodStart = $checkDate->subDays(
            $this->referencePeriod - 1
        );

        $intervals = $this->getIntervalsWithinPeriod(
            $normalisedStays,
            $periodStart,
            $checkDate
        );

        $mergedIntervals = $this->mergeIntervals($intervals);

        $daysUsed = 0;

        foreach ($mergedIntervals as $interval) {
            $daysUsed += $this->inclusiveDaysBetween(
                $interval['start'],
                $interval['end']
            );
        }

        $daysRemaining = max(
            0,
            $this->maximumDays - $daysUsed
        );

        return [
            'date' => $checkDate->format('Y-m-d'),
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $checkDate->format('Y-m-d'),
            'days_used' => $daysUsed,
            'days_remaining' => $daysRemaining,
            'maximum_days' => $this->maximumDays,
            'reference_period' => $this->referencePeriod,
            'compliant' => $daysUsed <= $this->maximumDays,
            'stay_breakdown' => $this->buildStayBreakdown(
                $normalisedStays,
                $periodStart,
                $checkDate
            ),
        ];
    }

    /**
     * Return the number of days remaining on a particular date.
     */
    public function remainingDays(array $stays, string $date): int
    {
        return $this->calculate($stays, $date)['days_remaining'];
    }

    /**
     * Get stay intervals clipped to the requested rolling period.
     *
     * Future stays are ignored.
     */
    private function getIntervalsWithinPeriod(
        array $stays,
        CarbonImmutable $periodStart,
        CarbonImmutable $checkDate,
    ): array {
        $intervals = [];

        foreach ($stays as $stay) {
            $entry = $stay['entry'];

            if ($entry->gt($checkDate)) {
                continue;
            }

            $exit = $stay['exit'] ?? $checkDate;

            if ($exit->lt($periodStart)) {
                continue;
            }

            $start = $entry->max($periodStart);
            $end = $exit->min($checkDate);

            if ($start->gt($end)) {
                continue;
            }

            // A verified permit or long-stay visa only excludes
            // the period physically spent in its issuing country.
            $isExemptInIssuingCountry =
                in_array(
                    $stay['type'],
                    ['residence_permit', 'long_stay_visa'],
                    true
                )
                && $stay['country_code'] === $stay['permit_country_code'];

            if ($isExemptInIssuingCountry) {
                continue;
            }

            // Check membership for every day. This handles countries
            // which joined Schengen during a recorded trip.
            for (
                $day = $start;
                $day->lte($end);
                $day = $day->addDay()
            ) {
                $date = $day->format('Y-m-d');

                if (
                    $this->countryRegistry->isSchengen(
                        $stay['country_code'],
                        $date
                    )
                ) {
                    $intervals[] = [
                        'start' => $day,
                        'end' => $day,
                    ];
                }
            }
        }

        return $intervals;
    }

    /**
     * Explain which days from each recorded stay contribute to the total.
     */
    private function buildStayBreakdown(
        array $stays,
        CarbonImmutable $periodStart,
        CarbonImmutable $checkDate,
    ): array {
        $countedDates = [];
        $breakdown = [];

        foreach ($stays as $stay) {
            $entry = $stay['entry'];
            $exit = $stay['exit'] ?? $checkDate;
            $beforeWindowEnd = $exit->min($periodStart->subDay());
            $daysBeforeWindow = $entry->lte($beforeWindowEnd)
                ? $this->inclusiveDaysBetween($entry, $beforeWindowEnd)
                : 0;
            $daysAfterDate = 0;

            if ($stay['exit'] !== null) {
                $afterDateStart = $entry->max($checkDate->addDay());
                $daysAfterDate = $afterDateStart->lte($exit)
                    ? $this->inclusiveDaysBetween($afterDateStart, $exit)
                    : 0;
            }

            $start = $entry->max($periodStart);
            $end = $exit->min($checkDate);
            $daysNotSchengen = 0;
            $daysExempt = 0;
            $daysOverlapping = 0;
            $countedDatesForStay = [];

            $isExemptInIssuingCountry = in_array(
                $stay['type'],
                ['residence_permit', 'long_stay_visa'],
                true
            ) && $stay['country_code'] === $stay['permit_country_code'];

            for ($day = $start; $day->lte($end); $day = $day->addDay()) {
                $date = $day->format('Y-m-d');

                if (! $this->countryRegistry->isSchengen($stay['country_code'], $date)) {
                    $daysNotSchengen++;

                    continue;
                }

                if ($isExemptInIssuingCountry) {
                    $daysExempt++;

                    continue;
                }

                if (isset($countedDates[$date])) {
                    $daysOverlapping++;

                    continue;
                }

                $countedDates[$date] = true;
                $countedDatesForStay[] = $date;
            }

            $breakdown[] = [
                'entry_date' => $entry->format('Y-m-d'),
                'exit_date' => $stay['exit']?->format('Y-m-d'),
                'country_code' => $stay['country_code'],
                'days_before_window' => $daysBeforeWindow,
                'days_after_date' => $daysAfterDate,
                'days_not_schengen' => $daysNotSchengen,
                'days_exempt' => $daysExempt,
                'days_overlapping' => $daysOverlapping,
                'days_counted' => count($countedDatesForStay),
                'counted_dates' => $countedDatesForStay,
            ];
        }

        return $breakdown;
    }

    /**
     * Merge overlapping or adjacent intervals.
     *
     * This prevents double-counting days when recorded trips overlap.
     */
    private function mergeIntervals(array $intervals): array
    {
        if ($intervals === []) {
            return [];
        }

        usort(
            $intervals,
            fn (array $a, array $b): int => $a['start']->getTimestamp()
                <=> $b['start']->getTimestamp()
        );

        $merged = [];
        $current = array_shift($intervals);

        foreach ($intervals as $interval) {
            $nextDay = $current['end']->addDay();

            if ($interval['start']->lte($nextDay)) {
                $current['end'] = $current['end']->max(
                    $interval['end']
                );

                continue;
            }

            $merged[] = $current;
            $current = $interval;
        }

        $merged[] = $current;

        return $merged;
    }

    /**
     * Count calendar days inclusively.
     *
     * Both entry and exit dates count as days in the Schengen Area.
     */
    private function inclusiveDaysBetween(
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): int {
        if ($end->lt($start)) {
            return 0;
        }

        return (int) $start->diffInDays($end) + 1;
    }
}
