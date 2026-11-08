<?php

namespace Tests\Feature;

use Tests\TestCase;

class SchengenApiTest extends TestCase
{
    public function test_calculate_endpoint_returns_allowance(): void
    {
        $response = $this->postJson('/api/schengen/calculate', [
            'date' => '2026-11-08',
            'stays' => [
                [
                    'entry' => '2026-09-01',
                    'exit' => '2026-09-20',
                    'country_code' => 'FR',
                ],
                [
                    'entry' => '2026-10-01',
                    'exit' => '2026-10-15',
                    'country_code' => 'ES',
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.days_used', 35)
            ->assertJsonPath('data.days_remaining', 55)
            ->assertJsonPath('data.compliant', true);
    }

    public function test_calculate_endpoint_accepts_no_previous_stays(): void
    {
        $response = $this->postJson('/api/schengen/calculate', [
            'date' => '2026-11-08',
            'stays' => [],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.days_used', 0)
            ->assertJsonPath('data.days_remaining', 90);
    }

    public function test_calculate_endpoint_explains_days_inside_and_outside_the_window(): void
    {
        $stays = [
            [
                'entry' => '2026-04-14',
                'exit' => '2026-05-20',
                'country_code' => 'PT',
            ],
            [
                'entry' => '2026-08-16',
                'exit' => '2026-09-04',
                'country_code' => 'PT',
            ],
            [
                'entry' => '2026-11-07',
                'exit' => '2026-12-14',
                'country_code' => 'PT',
            ],
        ];

        $response = $this->postJson('/api/schengen/calculate', [
            'date' => '2026-12-15',
            'stays' => $stays,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.period_start', '2026-06-19')
            ->assertJsonPath('data.days_used', 58)
            ->assertJsonPath('data.days_remaining', 32)
            ->assertJsonPath('data.stay_breakdown.0.days_before_window', 37)
            ->assertJsonPath('data.stay_breakdown.0.days_counted', 0)
            ->assertJsonPath('data.stay_breakdown.1.days_counted', 20)
            ->assertJsonPath('data.stay_breakdown.2.days_counted', 38);

        $nextYearResponse = $this->postJson('/api/schengen/calculate', [
            'date' => '2027-12-15',
            'stays' => $stays,
        ]);

        $nextYearResponse
            ->assertOk()
            ->assertJsonPath('data.days_used', 0)
            ->assertJsonPath('data.days_remaining', 90)
            ->assertJsonPath('data.stay_breakdown.0.days_counted', 0)
            ->assertJsonPath('data.stay_breakdown.1.days_counted', 0)
            ->assertJsonPath('data.stay_breakdown.2.days_counted', 0);
    }

    public function test_check_trip_endpoint_accepts_a_valid_trip(): void
    {
        $response = $this->postJson('/api/schengen/check-trip', [
            'entry_date' => '2026-11-20',
            'exit_date' => '2026-12-10',
            'stays' => [],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_maximum_stay_endpoint_returns_a_result(): void
    {
        $response = $this->postJson('/api/schengen/maximum-stay', [
            'entry_date' => '2026-11-20',
            'stays' => [],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_countries_endpoint_returns_schengen_countries(): void
    {
        $response = $this->getJson('/api/schengen/countries');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.count', 29);

        $countries = $response->json('data.countries');

        $this->assertContains('FR', $countries);
        $this->assertContains('DE', $countries);
        $this->assertContains('BG', $countries);
        $this->assertNotContains('IE', $countries);
        $this->assertNotContains('CY', $countries);
    }

    public function test_calculate_endpoint_rejects_invalid_dates(): void
    {
        $response = $this->postJson('/api/schengen/calculate', [
            'date' => '08-11-2026',
            'stays' => [],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date']);
    }

    public function test_calculate_endpoint_rejects_invalid_country_codes(): void
    {
        $response = $this->postJson('/api/schengen/calculate', [
            'date' => '2026-11-08',
            'stays' => [
                [
                    'entry' => '2026-09-01',
                    'exit' => '2026-09-10',
                    'country_code' => 'ZZ',
                ],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['stays']);
    }

    public function test_check_trip_rejects_exit_before_entry(): void
    {
        $response = $this->postJson('/api/schengen/check-trip', [
            'entry_date' => '2026-11-20',
            'exit_date' => '2026-11-10',
            'stays' => [],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['exit_date']);
    }
}