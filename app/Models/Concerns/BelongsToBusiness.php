<?php

namespace App\Models\Concerns;

use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Keeps records inside the current business: queries only return the current
 * business's rows and new rows are stamped with it automatically.
 */
trait BelongsToBusiness
{
    public static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope('business', function (Builder $query) {
            if ($id = tenancy()->businessId()) {
                $query->where($query->qualifyColumn('business_id'), $id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->business_id) && ($id = tenancy()->businessId())) {
                $model->business_id = $id;
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    /** Skip the automatic filter, e.g. for platform-wide maintenance. */
    public static function everywhere(): Builder
    {
        return static::withoutGlobalScope('business');
    }
}
