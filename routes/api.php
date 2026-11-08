<?php

use Illuminate\Support\Facades\Route;
use Simplydigital\SchengenCalculator\Http\Controllers\SchengenApiController;

Route::prefix(config('schengen.api.prefix', 'api/schengen'))
    ->middleware(config('schengen.api.middleware', ['api', 'throttle:60,1']))
    ->group(function () {
        Route::post('/calculate', [
            SchengenApiController::class,
            'calculate',
        ])->name('schengen.calculate');

        Route::post('/check-trip', [
            SchengenApiController::class,
            'checkTrip',
        ])->name('schengen.check-trip');

        Route::post('/maximum-stay', [
            SchengenApiController::class,
            'maximumStay',
        ])->name('schengen.maximum-stay');

        Route::get('/countries', [
            SchengenApiController::class,
            'countries',
        ])->name('schengen.countries');
    });