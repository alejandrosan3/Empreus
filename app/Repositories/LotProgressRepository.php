<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use MeiliSearch\Client;
use App\Models\Lot;

class LotProgressRepository
{
    public function getLots(array $filters, string $sortBy, string $sortDirection, string $searchTerm = ''): LengthAwarePaginator
    {
        $perPage = 20;
        $page    = request('page', 1);

        $meiliMap = [
            'builder'    => 'builder_id',
            'lot_number' => 'lot_number',
            'supervisor' => 'supervisor_id',
            'phase'      => 'phase',
        ];

        $filterClauses = [];
        foreach ($filters as $key => $vals) {
            if (empty($vals) || ! isset($meiliMap[$key])) {
                continue;
            }
            $field = $meiliMap[$key];
            $ors   = array_map(fn($v) => is_numeric($v)
                ? "{$field} = {$v}"
                : "{$field} = \"{$v}\"",
            $vals);
            $filterClauses[] = '('.implode(' OR ', $ors).')';
        }

        $client = new Client(
            config('scout.meilisearch.host'),
            config('scout.meilisearch.key')
        );
        $index = $client->index('lots');

        $sortField = $meiliMap[$sortBy] ?? $sortBy;

        $result = $index->search($searchTerm, [
            'filter' => $filterClauses,
            'sort'   => ["{$sortField}:{$sortDirection}"],
            'limit'  => $perPage,
            'offset' => ($page - 1) * $perPage,
        ]);

        $data  = $result->toArray();
        $hits  = $data['hits']   ?? [];
        $total = $data['nbHits'] ?? count($hits);

        $ids    = array_column($hits, 'id');
        $models = Lot::whereIn('id', $ids)
            ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
            ->get();

        return new LengthAwarePaginator(
            $models,
            $total,
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
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
        $client = new Client(
            config('scout.meilisearch.host'),
            config('scout.meilisearch.key')
        );
        $index = $client->index('lots');

        $meiliMap = [
            'builder'    => 'builder_id',
            'lot_number' => 'lot_number',
            'supervisor' => 'supervisor_id',
            'phase'      => 'phase',
        ];

        $facetFields = [];
        foreach ($columns as $col) {
            if (! empty($col['filterable']) && $col['value'] !== 'subdivision' && isset($meiliMap[$col['value']])) {
                $facetFields[] = $meiliMap[$col['value']];
            }
        }

        $filterClauses = [];
        foreach ($selectedFilters as $uiKey => $values) {
            if (empty($values) || ! isset($meiliMap[$uiKey])) {
                continue;
            }
            $field = $meiliMap[$uiKey];
            $ors = array_map(fn($v) => is_numeric($v)
                ? "{$field} = {$v}"
                : "{$field} = \"{$v}\"",
            $values);
            $filterClauses[] = '(' . implode(' OR ', $ors) . ')';
        }

        $searchResult = $index->search('', [
            'filter' => $filterClauses,
            'facets' => $facetFields,
            'limit'  => 0,
        ]);
        $facetDist = $searchResult->getFacetDistribution();

        $filters = [];
        foreach ($columns as $col) {
            $key = $col['value'];

            if (empty($col['filterable'])) {
                continue;
            }

            if ($key === 'subdivision') {
                $q = Auth::user()->lotsForCurrentCompany();
                $other = \Illuminate\Support\Arr::except($selectedFilters, ['subdivision']);
                $this->applyLotFilters($q, $other);

                $filters[$key] = $q
                    ->clone()
                    ->select('subdivision','phase')
                    ->groupBy('subdivision','phase')
                    ->orderBy('subdivision','asc')
                    ->get()
                    ->map(fn($i) => [
                        'value' => $i->subdivision.'|'.$i->phase,
                        'label' => $i->subdivision . ($i->phase ? " ({$i->phase})" : ''),
                    ])->toArray();
                continue;
            }

            if (isset($meiliMap[$key])) {
                $field   = $meiliMap[$key];
                $buckets = $facetDist[$field] ?? [];
                $filters[$key] = [];

                if (in_array($key, ['builder','supervisor'])) {
                    $modelClass = $key === 'builder'
                        ? \App\Models\Builder::class
                        : \App\Models\Supervisor::class;
                    foreach (array_keys($buckets) as $id) {
                        $idInt = (int) $id;
                        $label = optional($modelClass::find($idInt))->name ?? $idInt;
                        $filters[$key][] = ['value' => $idInt, 'label' => $label];
                    }
                } elseif ($key === 'lot_number' || $key === 'phase') {
                    foreach (array_keys($buckets) as $val) {
                        $filters[$key][] = ['value' => $val, 'label' => $val];
                    }
                }
            }
        }

        return $filters;
    }
}
