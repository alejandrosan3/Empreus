<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Lot;

class LotProgressRepository
{
    public function getLots(array $filters, string $sortBy, string $sortDirection, string $searchTerm = '')
    {
        if ($searchTerm !== '') {
            return Lot::search($searchTerm)
                ->query(function ($query) use ($filters) {
                    $query->where('company_id', session('company_id'));
                    $this->applyLotFilters($query, $filters);
                })
                ->paginate(20);
        }

        $query = Auth::user()->lotsForCurrentCompany();

        $this->applyLotFilters($query, $filters);

        $this->applySort($query, $sortBy, $sortDirection);

        return $query->paginate(20);
    }

    private function applyLotFilters($query, array $filters): void
    {
        foreach ($filters as $column => $values) {
            if (empty($values)) {
                continue;
            }

            switch ($column) {
                case 'builder':
                    $query->whereHas('builder', fn($q) => $q->whereIn('id', $values));
                    break;
                case 'subdivision':
                    $query->where(function ($q) use ($values) {
                        foreach ($values as $filter) {
                            [$subdivision, $phase] = explode('|', $filter);
                            $q->orWhere(fn($q2) => $q2->where('subdivision', $subdivision)
                                                ->where('phase', $phase));
                        }
                    });
                    break;
                case 'lot_number':
                    $query->whereIn('lot_number', $values);
                    break;
                case 'supervisor':
                    $query->whereHas('supervisor', fn($q) => $q->whereIn('id', $values));
                    break;
                case 'phase':
                    $query->whereIn('phase', $values);
                    break;
            }
        }
    }

    private function applySort($query, string $sortBy, string $sortDirection): void
    {
        if ($sortBy === 'builder') {
            $query->join('builders', 'lots.builder_id', '=', 'builders.id')
                  ->orderBy('builders.name', $sortDirection);
        } elseif ($sortBy === 'status') {
            $query->join('status_types', 'lots.status_type_id', '=', 'status_types.id')
                  ->orderBy('status_types.name', $sortDirection);
        } elseif ($sortBy === 'lot_number') {
            $query->orderByRaw("CAST(lot_number AS UNSIGNED) {$sortDirection}, lot_number ASC");
        } elseif ($sortBy === 'supervisor') {
            $query->join('supervisors', 'lots.supervisor_id', '=', 'supervisors.id')
                  ->orderBy('supervisors.name', $sortDirection);
        } elseif ($sortBy === 'last_completed_date') {
            $query->select('lots.*')
                  ->selectRaw('(SELECT MAX(t.ended_at) FROM tasks t WHERE t.lot_id = lots.id) as last_completed_date')
                  ->groupBy('lots.id')
                  ->orderBy('last_completed_date', $sortDirection);
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }
    }

    public function filterOptions(array $selectedFilters, array $columns): array
    {
        $query   = Auth::user()->lotsForCurrentCompany();
        $filters = [];

        foreach ($columns as $column) {
            if (empty($column['filterable'])) {
                continue;
            }
            $colKey      = $column['value'];
            $optionsQuery = clone $query;

            foreach ($selectedFilters as $key => $values) {
                if ($key === $colKey || empty($values)) {
                    continue;
                }
                switch ($key) {
                    case 'builder':
                        $optionsQuery->whereHas('builder', fn($q) => $q->whereIn('id', $values));
                        break;
                    case 'subdivision':
                        $optionsQuery->where(function ($q) use ($values) {
                            foreach ($values as $val) {
                                [$sub,$ph] = explode('|',$val);
                                $q->orWhere(fn($q2)=>$q2->where('subdivision',$sub)->where('phase',$ph));
                            }
                        });
                        break;
                    case 'lot_number':
                        $optionsQuery->whereIn('lot_number', $values);
                        break;
                    case 'supervisor':
                        $optionsQuery->whereHas('supervisor', fn($q)=>$q->whereIn('id',$values));
                        break;
                    case 'phase':
                        $optionsQuery->whereIn('phase', $values);
                        break;
                }
            }

            switch ($colKey) {
                case 'builder':
                    $filters[$colKey] = $optionsQuery->clone()
                        ->join('builders','lots.builder_id','=','builders.id')
                        ->select('builders.id','builders.name')
                        ->distinct()
                        ->orderBy('builders.name','asc')
                        ->get()
                        ->map(fn($b)=>['value'=>$b->id,'label'=>$b->name])
                        ->toArray();
                    break;
                case 'subdivision':
                    $filters[$colKey] = $optionsQuery->clone()
                        ->select('subdivision','phase')
                        ->groupBy('subdivision','phase')
                        ->orderBy('subdivision','asc')
                        ->get()
                        ->map(fn($i)=>['value'=>$i->subdivision.'|'.$i->phase,'label'=>$i->subdivision.($i->phase ? " ({$i->phase})" : '')])
                        ->toArray();
                    break;
                case 'lot_number':
                    $filters[$colKey] = $optionsQuery->clone()
                        ->distinct()
                        ->orderBy('lot_number','asc')
                        ->pluck('lot_number')
                        ->toArray();
                    break;
                case 'supervisor':
                    $filters[$colKey] = $optionsQuery->clone()
                        ->join('supervisors','lots.supervisor_id','=','supervisors.id')
                        ->select('supervisors.id','supervisors.name')
                        ->distinct()
                        ->orderBy('supervisors.name','asc')
                        ->get()
                        ->map(fn($s)=>['value'=>$s->id,'label'=>$s->name])
                        ->toArray();
                    break;
                case 'phase':
                    $filters[$colKey] = $optionsQuery->clone()
                        ->select('phase')
                        ->distinct()
                        ->orderBy('phase','asc')
                        ->pluck('phase')
                        ->toArray();
                    break;
            }
        }

        return $filters;
    }
}
