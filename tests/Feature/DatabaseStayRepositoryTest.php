<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Simplydigital\SchengenCalculator\Services\DatabaseStayRepository;
use Simplydigital\SchengenCalculator\Services\StayCalculator;
use Tests\TestCase;
use Tests\Fixtures\TestTraveller;

class DatabaseStayRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_travellers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    public function test_package_migration_creates_the_stays_table(): void
    {
        $this->assertTrue(
            Schema::hasTable('schengen_stays')
        );

        $this->assertTrue(
            Schema::hasColumn('schengen_stays', 'country_code')
        );

        $this->assertTrue(
            Schema::hasColumn('schengen_stays', 'type')
        );

        $this->assertTrue(
            Schema::hasColumn('schengen_stays', 'permit_country_code')
        );
    }

    public function test_it_saves_and_retrieves_stays_through_the_traveller_relationship(): void
    {
        $traveller = TestTraveller::create([
            'name' => 'Test Traveller',
        ]);

        $traveller->schengenStays()->create([
            'entry_date' => '2026-01-01',
            'exit_date' => '2026-01-05',
            'country_code' => 'FR',
            'type' => 'short_stay',
        ]);

        $traveller->schengenStays()->create([
            'entry_date' => '2026-01-06',
            'exit_date' => '2026-01-10',
            'country_code' => 'DE',
            'type' => 'short_stay',
        ]);

        $repository = app(DatabaseStayRepository::class);

        $stays = $repository->getFor($traveller);

        $this->assertCount(2, $stays);

        $this->assertSame('2026-01-01', $stays[0]['entry']);
        $this->assertSame('2026-01-05', $stays[0]['exit']);
        $this->assertSame('FR', $stays[0]['country_code']);

        $this->assertSame('2026-01-06', $stays[1]['entry']);
        $this->assertSame('2026-01-10', $stays[1]['exit']);
        $this->assertSame('DE', $stays[1]['country_code']);
    }

    public function test_it_calculates_allowance_from_database_records(): void
    {
        $traveller = TestTraveller::create([
            'name' => 'Test Traveller',
        ]);

        $traveller->schengenStays()->createMany([
            [
                'entry_date' => '2026-01-01',
                'exit_date' => '2026-01-05',
                'country_code' => 'FR',
                'type' => 'short_stay',
            ],
            [
                'entry_date' => '2026-01-06',
                'exit_date' => '2026-01-10',
                'country_code' => 'DE',
                'type' => 'short_stay',
            ],
        ]);

        $repository = app(DatabaseStayRepository::class);
        $calculator = app(StayCalculator::class);

        $stays = $repository->getFor($traveller);

        $result = $calculator->calculate(
            $stays,
            '2026-01-10'
        );

        $this->assertSame(10, $result['days_used']);
        $this->assertSame(80, $result['days_remaining']);
        $this->assertTrue($result['compliant']);
    }

    public function test_it_preserves_country_and_permit_information(): void
    {
        $traveller = TestTraveller::create([
            'name' => 'Permit Holder',
        ]);

        $traveller->schengenStays()->createMany([
            [
                'entry_date' => '2026-01-01',
                'exit_date' => '2026-01-30',
                'country_code' => 'FR',
                'type' => 'residence_permit',
                'permit_country_code' => 'FR',
            ],
            [
                'entry_date' => '2026-01-31',
                'exit_date' => '2026-02-04',
                'country_code' => 'DE',
                'type' => 'short_stay',
            ],
        ]);

        $repository = app(DatabaseStayRepository::class);
        $calculator = app(StayCalculator::class);

        $stays = $repository->getFor($traveller);

        $this->assertSame('residence_permit', $stays[0]['type']);
        $this->assertSame('FR', $stays[0]['permit_country_code']);

        $result = $calculator->calculate(
            $stays,
            '2026-02-04'
        );

        // The French permit period is excluded; the German visit counts.
        $this->assertSame(5, $result['days_used']);
    }

    public function test_it_retrieves_an_ongoing_stay_with_a_null_exit_date(): void
    {
        $traveller = TestTraveller::create([
            'name' => 'Traveller With Ongoing Stay',
        ]);

        $traveller->schengenStays()->create([
            'entry_date' => '2026-01-01',
            'exit_date' => null,
            'country_code' => 'PT',
            'type' => 'short_stay',
        ]);

        $repository = app(DatabaseStayRepository::class);

        $stays = $repository->getFor($traveller);

        $this->assertNull($stays[0]['exit']);

        $calculator = app(StayCalculator::class);

        $result = $calculator->calculate(
            $stays,
            '2026-01-10'
        );

        $this->assertSame(10, $result['days_used']);
    }

    public function test_it_does_not_mix_stays_from_different_travellers(): void
    {
        $firstTraveller = TestTraveller::create([
            'name' => 'First Traveller',
        ]);

        $secondTraveller = TestTraveller::create([
            'name' => 'Second Traveller',
        ]);

        $firstTraveller->schengenStays()->create([
            'entry_date' => '2026-01-01',
            'exit_date' => '2026-01-10',
            'country_code' => 'FR',
            'type' => 'short_stay',
        ]);

        $secondTraveller->schengenStays()->create([
            'entry_date' => '2026-01-01',
            'exit_date' => '2026-01-05',
            'country_code' => 'DE',
            'type' => 'short_stay',
        ]);

        $repository = app(DatabaseStayRepository::class);

        $firstStays = $repository->getFor($firstTraveller);
        $secondStays = $repository->getFor($secondTraveller);

        $this->assertCount(1, $firstStays);
        $this->assertCount(1, $secondStays);

        $this->assertSame('FR', $firstStays[0]['country_code']);
        $this->assertSame('DE', $secondStays[0]['country_code']);
    }
}