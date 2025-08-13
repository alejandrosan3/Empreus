<?php

return [
    // Canonical list of house types (keys are internal slugs; values are display labels)
    'types' => [
        'town_house'    => 'Town House',
        'single_family' => 'Single Family',
    ],

    // Abbreviation mapping used when parsing the subdivision field (whole word, case-insensitive)
    // e.g., "Maple Grove TH" => Town House
    'abbreviations' => [
        'TH' => 'town_house',
        'SF' => 'single_family',
    ],

    // Fallback rules when no abbreviation is present in subdivision
    'fallback_by_floors' => [
        // exact floors mapping
        '1' => 'single_family',

        // anything greater than 1
        'more_than_one' => 'town_house',

        // optional global fallback if needed later
        'default' => 'single_family',
    ],
];
