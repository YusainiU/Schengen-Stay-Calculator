<?php

namespace Simplydigital\SchengenCalculator\DTO;

use Carbon\CarbonImmutable;

class TripResult
{
    public function __construct(
        public readonly CarbonImmutable $entryDate,
        public readonly ?CarbonImmutable $exitDate,
        public readonly int $daysAllowed,
        public readonly bool $compliant,
        public readonly ?CarbonImmutable $lastAllowedDay,
        public readonly array $dailyBreakdown = [],
    ) {}

    public function toArray(): array
    {
        return [
            'entry_date' => $this->entryDate->toDateString(),
            'exit_date' => $this->exitDate?->toDateString(),
            'days_allowed' => $this->daysAllowed,
            'compliant' => $this->compliant,
            'last_allowed_day' => $this->lastAllowedDay?->toDateString(),
            'daily_breakdown' => $this->dailyBreakdown,
        ];
    }
}
