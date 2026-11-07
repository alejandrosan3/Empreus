<?php

/*
|--------------------------------------------------------------------------
| Defaults for organization and personal settings
|--------------------------------------------------------------------------
|
| Organizations change these on Settings › Organization, and each person on
| Settings › My preferences. Anything not set falls back to these values.
|
*/

return [
    'defaults' => [
        'currency_symbol' => '$',
    ],

    'preferences' => [
        'rows_per_page' => 20,
    ],

    'rows_per_page_options' => [10, 20, 50, 100],
];
