<?php

namespace Simplydigital\SchengenCalculator\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Simplydigital\SchengenCalculator\Calculator;
use Simplydigital\SchengenCalculator\Services\SchengenCountryRegistry;

class SchengenApiController extends Controller
{
    public function __construct(
        private readonly Calculator $calculator,
        private readonly SchengenCountryRegistry $countries,
    ) {}

    /**
     * Calculate days used and remaining on a particular date.
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'stays' => ['required', 'array'],
            'stays.*.entry' => ['required', 'date_format:Y-m-d'],
            'stays.*.exit' => ['nullable', 'date_format:Y-m-d'],
            'stays.*.country_code' => ['required', 'string', 'size:2'],
            'stays.*.type' => [
                'sometimes',
                'string',
                'in:short_stay,residence_permit,long_stay_visa',
            ],
            'stays.*.permit_country_code' => [
                'nullable',
                'string',
                'size:2',
            ],
        ]);

        try {
            $result = $this->calculator->calculate(
                $validated['stays'],
                $validated['date'],
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'stays' => [$exception->getMessage()],
            ]);
        }
    }

    /**
     * Check whether a proposed trip complies with the 90/180 rule.
     */
    public function checkTrip(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date_format:Y-m-d'],
            'exit_date' => ['required', 'date_format:Y-m-d'],
            'stays' => ['required', 'array'],
            'stays.*.entry' => ['required', 'date_format:Y-m-d'],
            'stays.*.exit' => ['nullable', 'date_format:Y-m-d'],
            'stays.*.country_code' => ['required', 'string', 'size:2'],
            'stays.*.type' => [
                'sometimes',
                'string',
                'in:short_stay,residence_permit,long_stay_visa',
            ],
            'stays.*.permit_country_code' => [
                'nullable',
                'string',
                'size:2',
            ],
        ]);

        if ($validated['exit_date'] < $validated['entry_date']) {
            throw ValidationException::withMessages([
                'exit_date' => [
                    'The exit date must be on or after the entry date.',
                ],
            ]);
        }

        try {
            $result = $this->calculator->checkTrip(
                $validated['stays'],
                $validated['entry_date'],
                $validated['exit_date'],
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'stays' => [$exception->getMessage()],
            ]);
        }
    }

    /**
     * Calculate the maximum permitted continuous stay.
     */
    public function maximumStay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date_format:Y-m-d'],
            'stays' => ['required', 'array'],
            'stays.*.entry' => ['required', 'date_format:Y-m-d'],
            'stays.*.exit' => ['nullable', 'date_format:Y-m-d'],
            'stays.*.country_code' => ['required', 'string', 'size:2'],
            'stays.*.type' => [
                'sometimes',
                'string',
                'in:short_stay,residence_permit,long_stay_visa',
            ],
            'stays.*.permit_country_code' => [
                'nullable',
                'string',
                'size:2',
            ],
        ]);

        try {
            $result = $this->calculator->maximumStay(
                $validated['stays'],
                $validated['entry_date'],
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'stays' => [$exception->getMessage()],
            ]);
        }
    }

    /**
     * Return the recognised Schengen countries as of a given date.
     */
    public function countries(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $date = $validated['date'] ?? now('UTC')->toDateString();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'count' => count($this->countries->allOnDate($date)),
                'countries' => $this->countries->allOnDate($date),
            ],
        ]);
    }
}