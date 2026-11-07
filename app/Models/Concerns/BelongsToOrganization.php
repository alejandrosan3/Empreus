<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Keeps records inside the current organization: queries only return the current
 * organization's rows and new rows are stamped with it automatically.
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $query) {
            if ($id = tenancy()->organizationId()) {
                $query->where($query->qualifyColumn('organization_id'), $id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->organization_id) && ($id = tenancy()->organizationId())) {
                $model->organization_id = $id;
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /** Skip the automatic filter, e.g. for platform-wide maintenance. */
    public static function everywhere(): Builder
    {
        return static::withoutGlobalScope('organization');
    }
}
