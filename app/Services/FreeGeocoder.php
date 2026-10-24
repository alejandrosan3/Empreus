<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Turns an address into coordinates using free, key-less services only.
 *
 * Results are meant to be stored on the lot (geocode once, reuse forever),
 * so maps never spend paid quota on geocoding.
 */
class FreeGeocoder
{
    private const JUNK_ADDRESSES = [
        'could not determine address', 'n/a', 'na', 'none', 'unknown',
        'tbd', 'tba', 'null', '0', '-',
    ];

    private float $lastNominatimCall = 0;

    /** True when the last lookup hit a network/service error (worth retrying soon). */
    public bool $lastLookupErrored = false;

    public static function isGeocodable(?string $address): bool
    {
        $address = trim((string) $address);

        return strlen($address) >= 5
            && ! in_array(strtolower($address), self::JUNK_ADDRESSES, true);
    }

    /**
     * Build the query sent to the geocoders, adding the default region when the
     * address looks like a bare street line (no comma, no ZIP code).
     */
    public function query(string $address): string
    {
        $address = trim($address);
        $region  = trim((string) config('services.geocoding.default_region'));

        $hasLocality = str_contains($address, ',') || preg_match('/\b\d{5}(-\d{4})?\b/', $address);

        return ($region !== '' && ! $hasLocality) ? "{$address}, {$region}" : $address;
    }

    /**
     * @return array{lat: float, lng: float, provider: string}|null
     */
    public function geocode(string $address): ?array
    {
        $this->lastLookupErrored = false;

        if (! self::isGeocodable($address)) {
            return null;
        }

        $query = $this->query($address);

        foreach ((array) config('services.geocoding.providers', ['census', 'nominatim']) as $provider) {
            try {
                $result = match (trim($provider)) {
                    'census'    => $this->census($query),
                    'nominatim' => $this->nominatim($query),
                    default     => null,
                };
            } catch (\Throwable $e) {
                Log::warning("Geocoding with {$provider} failed", ['address' => $query, 'error' => $e->getMessage()]);
                $this->lastLookupErrored = true;
                $result = null;
            }

            if ($result) {
                return $result + ['provider' => trim($provider)];
            }
        }

        return null;
    }

    /**
     * US Census Bureau geocoder: free, no key, US addresses only.
     */
    private function census(string $query): ?array
    {
        $response = Http::timeout(15)
            ->get('https://geocoding.geo.census.gov/geocoder/locations/onelineaddress', [
                'address'   => $query,
                'benchmark' => 'Public_AR_Current',
                'format'    => 'json',
            ]);

        $coords = $response->json('result.addressMatches.0.coordinates');

        if (! $response->ok() || ! isset($coords['x'], $coords['y'])) {
            return null;
        }

        return ['lat' => (float) $coords['y'], 'lng' => (float) $coords['x']];
    }

    /**
     * OpenStreetMap Nominatim: free, worldwide, max 1 request per second.
     */
    private function nominatim(string $query): ?array
    {
        $wait = 1.1 - (microtime(true) - $this->lastNominatimCall);
        if ($wait > 0) {
            usleep((int) ($wait * 1_000_000));
        }
        $this->lastNominatimCall = microtime(true);

        $email = (string) config('services.geocoding.contact_email');

        $response = Http::timeout(15)
            ->withHeaders(['User-Agent' => trim(config('app.name', 'Empreus') . ' lot-geocoder ' . $email)])
            ->get('https://nominatim.openstreetmap.org/search', array_filter([
                'q'      => $query,
                'format' => 'jsonv2',
                'limit'  => 1,
                'email'  => $email ?: null,
            ]));

        $first = $response->json('0');

        if (! $response->ok() || ! isset($first['lat'], $first['lon'])) {
            return null;
        }

        return ['lat' => (float) $first['lat'], 'lng' => (float) $first['lon']];
    }
}
