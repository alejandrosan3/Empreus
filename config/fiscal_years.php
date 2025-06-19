<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Fiscal Year Start Dates
    |--------------------------------------------------------------------------
    |
    | The company fiscal year and each builder's fiscal year can be customised
    | here. Builder keys should be the slug of the builder name (e.g. "dry"
    | for "Dry Homes"). Only builders defined below will have a preset in the
    | statistics page.
    */

    'company' => [
        'start_month' => 1,
        'start_day'   => 1,
    ],

    'builders' => [
        // 'builder-slug' => ['start_month' => <month>, 'start_day' => <day>]
        'dry' => [
            'start_month' => 10,
            'start_day'   => 1,
        ],
        // 'acme-homes' => [
        //     'start_month' => 7,
        //     'start_day'   => 15,
        // ],
    ],
];
