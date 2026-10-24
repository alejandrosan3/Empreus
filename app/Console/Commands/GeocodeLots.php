<?php

namespace App\Console\Commands;

use App\Models\Lot;
use App\Services\FreeGeocoder;
use Illuminate\Console\Command;

/**
 * Fills in missing lot coordinates with free geocoders and saves them, so the
 * statistics and lot maps can place lots without paid map quota.
 */
class GeocodeLots extends Command
{
    protected $signature = 'lots:geocode
                            {--limit=250 : Most lots to look up in one run}
                            {--retry-failed : Also retry lots that could not be found before}';

    protected $description = 'Find and save coordinates for lots that have none, using free geocoders';

    public function handle(FreeGeocoder $geocoder): int
    {
        $retryDays = (int) config('services.geocoding.retry_after_days', 30);

        $lots = Lot::query()
            ->where(fn ($q) => $q->whereNull('latitude')->orWhereNull('longitude'))
            ->whereNotNull('address')
            ->when(! $this->option('retry-failed'), fn ($q) => $q->where(fn ($qq) => $qq
                ->whereNull('geocode_attempted_at')
                ->orWhere('geocode_attempted_at', '<', now()->subDays($retryDays))
            ))
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->get(['id', 'address', 'latitude', 'longitude', 'geocode_attempted_at']);

        if ($lots->isEmpty()) {
            $this->info('Every lot with an address already has coordinates.');
            return self::SUCCESS;
        }

        $found = 0;
        $missed = 0;

        $this->withProgressBar($lots, function (Lot $lot) use ($geocoder, &$found, &$missed) {
            $result = $geocoder->geocode((string) $lot->address);

            // A network error says nothing about the address: try it again next run.
            if (! $result && $geocoder->lastLookupErrored) {
                $missed++;
                return;
            }

            $lot->geocode_attempted_at = now();
            if ($result) {
                $lot->latitude  = round($result['lat'], 7);
                $lot->longitude = round($result['lng'], 7);
            }

            // Quiet save: coordinates don't need search re-indexing.
            $lot->saveQuietly();

            $result ? $found++ : $missed++;
        });

        $this->newLine(2);
        $this->info("Located {$found} lot(s). Could not find {$missed}.");

        return self::SUCCESS;
    }
}
