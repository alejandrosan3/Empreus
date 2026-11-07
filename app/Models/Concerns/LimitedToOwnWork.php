<?php

namespace App\Models\Concerns;

use App\Models\Lot;
use App\Support\OwnWork;
use Illuminate\Database\Eloquent\Builder;

/**
 * For roles set to "Only their own work", tasks and lots only return the
 * person's own (see App\Support\OwnWork). Everyone else is unaffected.
 */
trait LimitedToOwnWork
{
    public static function bootLimitedToOwnWork(): void
    {
        static::addGlobalScope('own-work', function (Builder $query) {
            if (! $user = OwnWork::limitedUser()) {
                return;
            }

            $table = $query->getModel()->getTable();
            $query->getModel() instanceof Lot
                ? OwnWork::whereOwnLot($query->getQuery(), $user->id, $table)
                : OwnWork::whereOwnTask($query->getQuery(), $user->id, $table);
        });
    }
}
