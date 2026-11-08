<?php

namespace Simplydigital\SchengenCalculator;

use Simplydigital\SchengenCalculator\DTO\TripResult;
use Simplydigital\SchengenCalculator\Services\StayCalculator;
use Simplydigital\SchengenCalculator\Services\MaximumStayCalculator;
use Illuminate\Database\Eloquent\Model;
use Simplydigital\SchengenCalculator\Services\DatabaseStayRepository;
use Simplydigital\SchengenCalculator\Services\AvailabilityCalculator;

class Calculator
{
    public function __construct(
        private StayCalculator $stayCalculator,
        private MaximumStayCalculator $maximumStayCalculator,
        private DatabaseStayRepository $databaseRepository,
        private AvailabilityCalculator $availabilityCalculator
    ) {
    }

    public function calculateFor(
        Model $traveller,
        string $date
    ): array {
        $stays = $this->databaseRepository->getFor($traveller);

        return $this->calculate($stays, $date);
    }


    public function availableDaysOnFor(
        Model $traveller,
        string $date
    ): array {
        return $this->availableDaysOn(
            $this->databaseRepository->getFor($traveller),
            $date
        );
    }

    public function nextAvailableDateFor(
        Model $traveller,
        string $fromDate,
        int $searchDays = 366
    ): ?string {
        return $this->nextAvailableDate(
            $this->databaseRepository->getFor($traveller),
            $fromDate,
            $searchDays
        );
    }

    public function availabilityCalendarFor(
        Model $traveller,
        string $startDate,
        string $endDate
    ): array {
        return $this->availabilityCalendar(
            $this->databaseRepository->getFor($traveller),
            $startDate,
            $endDate
        );
    }

    public function daysReleasedFor(
        Model $traveller,
        string $fromDate,
        string $toDate
    ): array {
        return $this->daysReleased(
            $this->databaseRepository->getFor($traveller),
            $fromDate,
            $toDate
        );
    }



    public function availableDaysOn(
        array $stays,
        string $date
    ): array {
        return $this->availabilityCalculator->availableDaysOn(
            $stays,
            $date
        );
    }

    public function nextAvailableDate(
        array $stays,
        string $fromDate,
        int $searchDays = 366
    ): ?string {
        return $this->availabilityCalculator->nextAvailableDate(
            $stays,
            $fromDate,
            $searchDays
        );
    }

    public function availabilityCalendar(
        array $stays,
        string $startDate,
        string $endDate
    ): array {
        return $this->availabilityCalculator->availabilityCalendar(
            $stays,
            $startDate,
            $endDate
        );
    }

    public function daysReleased(
        array $stays,
        string $fromDate,
        string $toDate
    ): array {
        return $this->availabilityCalculator->daysReleased(
            $stays,
            $fromDate,
            $toDate
        );
    }


    public function maximumStayFor(
        Model $traveller,
        string $entryDate
    ): TripResult {
        $stays = $this->databaseRepository->getFor($traveller);

        return $this->maximumStay($stays, $entryDate);
    }

    public function checkTripFor(
        Model $traveller,
        string $entryDate,
        string $exitDate
    ): TripResult {
        $stays = $this->databaseRepository->getFor($traveller);

        return $this->checkTrip($stays, $entryDate, $exitDate);
    }

    public function calculate(array $stays, string $date): array
    {
        return $this->stayCalculator->calculate($stays, $date);
    }

    public function remainingDays(array $stays, string $date): int
    {
        return $this->calculate($stays, $date)['days_remaining'];
    }

    public function maximumStay(
        array $stays,
        string $entryDate
    ): TripResult {
        return $this->maximumStayCalculator->maximumStay(
            $stays,
            $entryDate
        );
    }

    public function checkTrip(
        array $stays,
        string $entryDate,
        string $exitDate
    ): TripResult {
        return $this->maximumStayCalculator->checkTrip(
            $stays,
            $entryDate,
            $exitDate
        );
    }
}
