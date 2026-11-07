<?php

use App\Support\Tenancy;

if (! function_exists('tenancy')) {
    /** The organization / business / division the current request works in. */
    function tenancy(): Tenancy
    {
        return app(Tenancy::class);
    }
}
