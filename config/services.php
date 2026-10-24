<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'maps_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    /*
    | Free geocoding used by `php artisan lots:geocode` (no API key, no quota
    | bill). The US Census geocoder is tried first, then OpenStreetMap's
    | Nominatim, which asks for a contact email and at most 1 request/second.
    | GEOCODE_DEFAULT_REGION is appended to addresses that have no city/state,
    | e.g. "Houston, TX".
    */
    'geocoding' => [
        'providers'      => array_filter(explode(',', env('GEOCODE_PROVIDERS', 'census,nominatim'))),
        'default_region' => env('GEOCODE_DEFAULT_REGION', ''),
        'contact_email'  => env('GEOCODE_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS')),
        'retry_after_days' => (int) env('GEOCODE_RETRY_AFTER_DAYS', 30),
    ],

];
