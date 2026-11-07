<?php

use App\Support\Tenancy;

if (! function_exists('tenancy')) {
    /** The organization / business / division the current request works in. */
    function tenancy(): Tenancy
    {
        return app(Tenancy::class);
    }
}

if (! function_exists('money')) {
    /** A price with the organization's currency symbol, e.g. "$1,250.00". */
    function money(int|float|string|null $amount): string
    {
        $symbol = tenancy()->organization()?->setting('currency_symbol') ?? config('organization.defaults.currency_symbol');

        return $symbol.number_format((float) $amount, 2);
    }
}

if (! function_exists('rows_per_page')) {
    /** How many rows a list shows for the signed-in person. */
    function rows_per_page(): int
    {
        return auth()->user()?->rowsPerPage() ?? config('organization.preferences.rows_per_page');
    }
}
