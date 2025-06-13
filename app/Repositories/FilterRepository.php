<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Lot;
use App\Models\Task;

class FilterRepository
{
    /**
     * Apply all provided filters to the query except the optional column.
     */
    public static function applyFilters(Builder $query, array $filters, ?string $except = null): Builder
    {
        foreach ($filters as $column => $values) {
            if ($column === $except || empty($values)) {
                continue;
            }

            $model = $query->getModel();

            switch ($column) {
                case 'builder':
                    if ($model instanceof Lot) {
                        $query->whereHas('builder', fn ($q) => $q->whereIn('id', $values));
                    } elseif ($model instanceof Task) {
                        $query->whereHas('lot.builder', fn ($q) => $q->whereIn('id', $values));
                    }
                    break;
                case 'subdivision':
                    if ($model instanceof Lot) {
                        $query->where(function ($q) use ($values) {
                            foreach ($values as $filter) {
                                [$sub, $phase] = explode('|', $filter);
                                $q->orWhere(fn ($q2) => $q2->where('subdivision', $sub)->where('phase', $phase));
                            }
                        });
                    } elseif ($model instanceof Task) {
                        $query->whereHas('lot', function ($q) use ($values) {
                            $q->where(function ($q2) use ($values) {
                                foreach ($values as $filter) {
                                    [$sub, $phase] = explode('|', $filter);
                                    $q2->orWhere(fn ($q3) => $q3->where('subdivision', $sub)->where('phase', $phase));
                                }
                            });
                        });
                    }
                    break;
                case 'lot_number':
                    if ($model instanceof Lot) {
                        $query->whereIn('lot_number', $values);
                    } elseif ($model instanceof Task) {
                        $query->whereHas('lot', fn ($q) => $q->whereIn('lot_number', $values));
                    }
                    break;
                case 'task_type':
                    if ($model instanceof Task) {
                        $query->whereIn('task_type_id', $values);
                    }
                    break;
                case 'status':
                    if ($model instanceof Task) {
                        $query->whereIn('status_type_id', $values);
                    }
                    break;
                case 'supervisor':
                    if ($model instanceof Lot) {
                        $query->whereHas('supervisor', fn ($q) => $q->whereIn('id', $values));
                    } elseif ($model instanceof Task) {
                        $query->whereHas('lot.supervisor', fn ($q) => $q->whereIn('id', $values));
                    }
                    break;
                case 'is_extra':
                    if ($model instanceof Task) {
                        $query->whereIn('is_extra', $values);
                    }
                    break;
                case 'phase':
                    if ($model instanceof Lot) {
                        $query->whereIn('phase', $values);
                    } elseif ($model instanceof Task) {
                        $query->whereHas('lot', fn ($q) => $q->whereIn('phase', $values));
                    }
                    break;
            }
        }

        return $query;
    }
}
