<?php

namespace Simplydigital\SchengenCalculator;

use Illuminate\Support\ServiceProvider;
use Simplydigital\SchengenCalculator\Services\StayCalculator;
use Simplydigital\SchengenCalculator\Services\StayNormalizer;
use Simplydigital\SchengenCalculator\Services\MaximumStayCalculator;
use Simplydigital\SchengenCalculator\Services\DatabaseStayRepository;
use Simplydigital\SchengenCalculator\Services\AvailabilityCalculator;
use Simplydigital\SchengenCalculator\Services\SchengenCountryRegistry;
use Simplydigital\SchengenCalculator\Services\CountryCodeValidator;

class SchengenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/schengen.php',
            'schengen'
        );

        $this->app->singleton(
            StayNormalizer::class,
            fn($app) => new StayNormalizer(
                $app->make(CountryCodeValidator::class)
            )
        );

        $this->app->singleton(
            MaximumStayCalculator::class,
            fn($app) => new MaximumStayCalculator(
                $app->make(StayCalculator::class),
                (int) $app['config']->get('schengen.maximum_days', 90),
                (int) $app['config']->get('schengen.reference_period', 180)
            )
        );

        $this->app->singleton(
            StayCalculator::class,
            fn($app) => new StayCalculator(
                $app->make(StayNormalizer::class),
                (int) config('schengen.maximum_days', 90),
                (int) config('schengen.reference_period', 180),
                $app->make(SchengenCountryRegistry::class),
            )
        );

        $this->app->singleton(
            DatabaseStayRepository::class
        );

        $this->app->singleton(
            Calculator::class,
            fn($app) => new Calculator(
                $app->make(StayCalculator::class),
                $app->make(MaximumStayCalculator::class),
                $app->make(DatabaseStayRepository::class),
                $app->make(AvailabilityCalculator::class)
            )
        );

        $this->app->alias(
            Calculator::class,
            'schengen.calculator'
        );

        $this->app->singleton(
            AvailabilityCalculator::class,
            fn($app) => new AvailabilityCalculator(
                $app->make(StayCalculator::class),
                (int) $app['config']->get('schengen.maximum_days', 90),
                (int) $app['config']->get('schengen.reference_period', 180)
            )
        );

        $this->app->singleton(
            SchengenCountryRegistry::class,
            fn() => new SchengenCountryRegistry()
        );

        $this->app->singleton(
            CountryCodeValidator::class,
            fn() => new CountryCodeValidator()
        );

    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/schengen.php'
            => config_path('schengen.php'),
        ], 'schengen-config');

        if (config('schengen.database.enabled', true)) {
            $this->loadMigrationsFrom(
                __DIR__ . '/../database/migrations'
            );
        }

        if (config('schengen.api.enabled', true)) {
            $this->loadRoutesFrom(
                __DIR__ . '/routes/api.php'
            );
        }

    }
}
