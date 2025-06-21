<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use MeiliSearch\Client;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use App\Models\Task;

class TaskProgressRepository
{
    public function getTasks(array $selectedFilters, string $sortBy, string $sortDirection, string $searchTerm = ''): LengthAwarePaginator
    {
        $perPage = 20;
        $page    = request('page', 1);

        // Map UI → Meili
        $meiliMap = [
            'builder'    => 'builder_id',
            'task_type'  => 'task_type_id',
            'status'     => 'status_id',
            'supervisor' => 'supervisor_id',
            'lot_number' => 'lot_number',
        ];

        // Build filters
        $filterClauses = [];
        foreach ($selectedFilters as $key => $vals) {
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

        // Meili search
        $client = new Client(
            config('scout.meilisearch.host'),
            config('scout.meilisearch.key')
        );
        $index = $client->index('tasks');

        $sortField = $meiliMap[$sortBy] ?? $sortBy;

        $result = $index->search($searchTerm, [
            'filter' => $filterClauses,
            'sort'   => ["{$sortField}:{$sortDirection}"],
            'limit'  => $perPage,
            'offset' => ($page - 1) * $perPage,
        ]);

        // Use toArray() to pull hits & total
        $data  = $result->toArray();
        $hits  = $data['hits']    ?? [];
        $total = $data['nbHits']  ?? count($hits);

        // Hydrate and preserve order
        $ids    = array_column($hits, 'id');
        $models = \App\Models\Task::whereIn('id', $ids)
            ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
            ->get();

        // Return paginator
        return new LengthAwarePaginator(
            $models,
            $total,
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }


    private function applyTaskFilters($query, array $filters): void
    {
        foreach ($filters as $column => $values) {
            if (empty($values)) {
                continue;
            }

            switch ($column) {
                case 'builder':
                    $query->whereHas('lot.builder', fn($q) => $q->whereIn('id', $values));
                    break;
                case 'subdivision':
                    $query->whereHas('lot', function ($q) use ($values) {
                        $q->where(function ($q2) use ($values) {
                            foreach ($values as $filter) {
                                [$sub, $ph] = explode('|', $filter);
                                $q2->orWhere(fn($q3) => $q3->where('subdivision', $sub)->where('phase', $ph));
                            }
                        });
                    });
                    break;
                case 'lot_number':
                    $query->whereHas('lot', fn($q) => $q->whereIn('lots.lot_number', $values));
                    break;
                case 'task_type':
                    $query->whereIn('task_type_id', $values);
                    break;
                case 'status':
                    $query->whereIn('tasks.status_type_id', $values);
                    break;
                case 'supervisor':
                    $query->whereHas('lot.supervisor', fn($q) => $q->whereIn('id', $values));
                    break;
                case 'is_extra':
                    $query->whereIn('is_extra', $values);
                    break;
                case 'phase':
                    $query->whereHas('lot', fn($q) => $q->whereIn('phase', $values));
                    break;
            }
        }
    }

    private function applySort($query, string $sortBy, string $sortDirection): void
    {
        if ($sortBy === 'builder') {
            $query->join('lots', 'tasks.lot_id', '=', 'lots.id')
                  ->join('builders', 'lots.builder_id', '=', 'builders.id')
                  ->orderBy('builders.name', $sortDirection);
        } elseif ($sortBy === 'subdivision') {
            $query->join('lots', 'tasks.lot_id', '=', 'lots.id')
                  ->orderBy('lots.subdivision', $sortDirection);
        } elseif ($sortBy === 'address') {
            $query->join('lots', 'tasks.lot_id', '=', 'lots.id')
                  ->orderBy('lots.address', $sortDirection);
        } elseif ($sortBy === 'lot_number') {
            $query->join('lots', 'tasks.lot_id', '=', 'lots.id')
                  ->orderByRaw("CAST(lots.lot_number AS UNSIGNED) {$sortDirection}, lots.lot_number ASC");
        } elseif ($sortBy === 'num_of_floors') {
            $query->join('lots', 'tasks.lot_id', '=', 'lots.id')
                  ->orderBy('lots.num_of_floors', $sortDirection);
        } elseif ($sortBy === 'floor_area') {
            $query->join('lots', 'tasks.lot_id', '=', 'lots.id')
                  ->orderBy('lots.floor_area', $sortDirection);
        } elseif ($sortBy === 'supervisor') {
            $query->join('lots', 'tasks.lot_id', '=', 'lots.id')
                  ->join('supervisors', 'lots.supervisor_id', '=', 'supervisors.id')
                  ->orderBy('supervisors.name', $sortDirection);
        } elseif ($sortBy === 'phase') {
            $query->join('lots', 'tasks.lot_id', '=', 'lots.id')
                  ->orderBy('lots.phase', $sortDirection);
        } elseif ($sortBy === 'task_type') {
            $query->join('task_types', 'tasks.task_type_id', '=', 'task_types.id')
                  ->orderBy('task_types.name', $sortDirection);
        } elseif ($sortBy === 'status') {
            $query->join('status_types', 'tasks.status_type_id', '=', 'status_types.id')
                  ->orderBy('status_types.name', $sortDirection);
        } elseif ($sortBy === 'is_extra') {
            $query->orderBy('tasks.is_extra', $sortDirection);
        } else {
            $query->orderBy('tasks.' . $sortBy, $sortDirection);
        }

        // We're adding this so the task ID is always returned correctly after sorting.
        $query->select('tasks.*');
    }

    public function filterOptions(array $selectedFilters, array $columns): array
    {
        // 1) Boot Meili
        $client = new \MeiliSearch\Client(
            config('scout.meilisearch.host'),
            config('scout.meilisearch.key')
        );
        $index = $client->index('tasks');

        // 2) Build a map from your dynamic column values → Meili fields
        $meiliMap = [
            'builder'    => 'builder_id',
            'task_type'  => 'task_type_id',
            'status'     => 'status_id',
            'supervisor' => 'supervisor_id',
            'lot_number' => 'lot_number',
            // others (e.g. address) are searchable but not filterable
        ];

        // 3) Collect just the columns you actually filter on via Meili
        $facetFields = [];
        foreach ($columns as $col) {
            if (
                ! empty($col['filterable'])
                && ! in_array($col['value'], ['subdivision','is_extra'])
                && isset($meiliMap[$col['value']])
            ) {
                $facetFields[] = $meiliMap[$col['value']];
            }
        }

        // 4) Turn selectedFilters into Meili filter clauses
        $filterClauses = [];
        foreach ($selectedFilters as $uiKey => $values) {
            if (
                empty($values)
                || ! isset($meiliMap[$uiKey])
            ) {
                continue;
            }
            $meiliField = $meiliMap[$uiKey];
            $ors = array_map(fn($v) => is_numeric($v)
                ? "{$meiliField} = {$v}"
                : "{$meiliField} = \"{$v}\""
            , $values);

            $filterClauses[] = '(' . implode(' OR ', $ors) . ')';
        }

        // 5) Ask Meili for only those facets
        try {
            $searchResult = $index->search('', [
                'filter' => $filterClauses,
                'facets' => $facetFields,
                'limit'  => 0,
            ]);
        } catch (\Meilisearch\Exceptions\ApiException $e) {
            if (str_contains($e->getMessage(), 'Invalid facet distribution')) {
                $settings = config('scout.meilisearch.index-settings.tasks', []);
                if (! empty($settings)) {
                    $index->updateSettings($settings);
                }
                $searchResult = $index->search('', [
                    'filter' => $filterClauses,
                    'facets' => $facetFields,
                    'limit'  => 0,
                ]);
            } else {
                throw $e;
            }
        }
        $facetDist = $searchResult->getFacetDistribution();

        // 6) Build your filters array by mirroring the $columns order
        $filters = [];
        foreach ($columns as $col) {
            $key = $col['value'];

            if (empty($col['filterable'])) {
                continue;
            }

            // A) Subdivision: SQL join as before
            if ($key === 'subdivision') {
                $q = Auth::user()->tasksForCurrentCompany();
                $other = Arr::except($selectedFilters, ['subdivision']);
                $this->applyTaskFilters($q, $other);

                $filters[$key] = $q
                    ->clone()
                    ->join('lots','tasks.lot_id','=','lots.id')
                    ->select('lots.subdivision','lots.phase')
                    ->groupBy('lots.subdivision','lots.phase')
                    ->orderBy('lots.subdivision','asc')
                    ->get()
                    ->map(fn($i)=>[
                        'value' => "{$i->subdivision}|{$i->phase}",
                        'label' => $i->subdivision . ($i->phase ? " ({$i->phase})" : ''),
                    ])->toArray();
                continue;
            }

            // B) is_extra: static
            if ($key === 'is_extra') {
                $filters[$key] = [
                    ['value'=>0,'label'=>'Standard'],
                    ['value'=>1,'label'=>'EPO'],
                ];
                continue;
            }

            // C) Everything else: Meili facet
            if (isset($meiliMap[$key])) {
                $meiliKey = $meiliMap[$key];
                $buckets  = $facetDist[$meiliKey] ?? [];

                $filters[$key] = [];

                // relational IDs → lookup labels
                if (in_array($key, ['builder','task_type','status','supervisor'])) {
                    $modelClass = match($key) {
                        'builder'    => \App\Models\Builder::class,
                        'task_type'  => \App\Models\TaskType::class,
                        'status'     => \App\Models\StatusType::class,
                        'supervisor' => \App\Models\Supervisor::class,
                    };

                    foreach (array_keys($buckets) as $id) {
                        $idInt = (int)$id;
                        $label = optional($modelClass::find($idInt))->name ?? $idInt;
                        $filters[$key][] = ['value'=>$idInt,'label'=>$label];
                    }
                }
                // lot_number is string
                elseif ($key === 'lot_number') {
                    foreach (array_keys($buckets) as $val) {
                        $filters[$key][] = ['value'=>$val,'label'=>$val];
                    }
                }

                continue;
            }
        }

        return $filters;
    }




}
