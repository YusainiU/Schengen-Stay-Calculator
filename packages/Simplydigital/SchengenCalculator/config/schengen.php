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

];
