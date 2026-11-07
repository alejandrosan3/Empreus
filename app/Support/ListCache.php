<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;

/**
 * Pickers cache their options (task types, statuses, tags) per division or business.
 * When a business changes one of its lists, forget those caches so the change shows right away.
 */
class ListCache
{
    public static function forgetBusiness(Business $business): void
    {
        Cache::forget("allTags.{$business->id}");

        foreach (Company::withTrashed()->where('business_id', $business->id)->pluck('id') as $divisionId) {
            foreach (['task_types', 'taskTypes', 'status_type_options', 'statusTypes'] as $prefix) {
                Cache::forget("$prefix.$divisionId");
            }
        }
    }
}
