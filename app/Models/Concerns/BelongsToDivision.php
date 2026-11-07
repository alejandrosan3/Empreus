<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Keeps records inside the current division: queries only return the current
 * division's rows and new rows are stamped with it automatically.
 */
trait BelongsToDivision
{
    public static function bootBelongsToDivision(): void
    {
        static::addGlobalScope('division', function (Builder $query) {
            if ($id = tenancy()->divisionId()) {
                $query->where($query->qualifyColumn('company_id'), $id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->company_id) && ($id = tenancy()->divisionId())) {
                $model->company_id = $id;
            }
        });
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /** Skip the automatic filter, e.g. for platform-wide maintenance. */
    public static function everywhere(): Builder
    {
        return static::withoutGlobalScope('division');
    }
}
