
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Schengen allowance
    |--------------------------------------------------------------------------
    */

    'maximum_days' => 90,

    'reference_period' => 180,

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    |
    | Database storage is optional. The calculator itself does not
    | require any database tables.
    |
    */

    'database' => [
        'enabled' => true,
        'table' => 'schengen_stays',
    ],

    'api' => [
        'enabled' => true,
        'prefix' => 'api/schengen',

        // Laravel middleware applied to all package API routes.
        'middleware' => [
            'api',
            'throttle:60,1',
        ],
    ],    

];
