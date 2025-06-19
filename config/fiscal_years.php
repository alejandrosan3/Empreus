<?php
use Carbon\Carbon;

$company = [
    'start_month' => 1,
    'start_day'   => 1,
];

$companyStart = Carbon::create(
    now()->month < $company['start_month'] ||
    (now()->month === $company['start_month'] && now()->day < $company['start_day'])
        ? now()->year - 1
        : now()->year,
    $company['start_month'],
    $company['start_day']
);
$company['start'] = $companyStart->toDateString();
$company['end'] = $companyStart->copy()->addYear()->subDay()->toDateString();

$builders = [
    // 'builder-slug' => ['start_month' => <month>, 'start_day' => <day>]
    'dry' => [
        'start_month' => 10,
        'start_day'   => 1,
    ],
    // 'acme-homes' => [
    //     'start_month' => 7,
    //     'start_day'   => 15,
    // ],
];

foreach ($builders as $slug => $data) {
    $startMonth = $data['start_month'] ?? $company['start_month'];
    $startDay = $data['start_day'] ?? $company['start_day'];
    $start = Carbon::create(
        now()->month < $startMonth ||
        (now()->month === $startMonth && now()->day < $startDay)
            ? now()->year - 1
            : now()->year,
        $startMonth,
        $startDay
    );
    $builders[$slug]['start'] = $start->toDateString();
    $builders[$slug]['end'] = $start->copy()->addYear()->subDay()->toDateString();
}

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

    'company' => $company,

    'builders' => $builders,
];
