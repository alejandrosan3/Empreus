<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Models\Builder;

class CacheInvalidationService
{
    /**
     * Clear caches for lot dropdowns.
     *
     * Only the lot cache for the specific builder/subdivision is removed. The
     * calling code should decide whether builder or subdivision caches also need
     * to be invalidated.
     */
    public static function forNewLot(int $companyId, int $builderId, string $subdivision): void
    {
        Cache::forget("lot_options.{$companyId}.{$builderId}.{$subdivision}");
    }

    /**
     * Clear caches for subdivision dropdowns.
     */
    public static function forNewSubdivision(int $companyId, ?int $builderId = null): void
    {
        if ($builderId !== null) {
            Cache::forget("subdivision_options.{$companyId}.{$builderId}");
        } else {
            foreach (Builder::pluck('id') as $id) {
                Cache::forget("subdivision_options.{$companyId}.{$id}");
            }
        }

        Cache::forget("subdivisions.{$companyId}");
    }

    /**
     * Clear caches for builder dropdowns.
     */
    public static function forNewBuilder(int $companyId): void
    {
        Cache::forget("builder_options.{$companyId}");
        Cache::forget("builders.{$companyId}");
    }

    /**
     * Clear caches for supervisor dropdowns.
     */
    public static function forNewSupervisor(int $companyId): void
    {
        Cache::forget("supervisor_options.{$companyId}");
        Cache::forget("supervisors.{$companyId}");
    }
}
