<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Task;

class TaskProgressRepository
{
    public function getTasks(array $filters, string $sortBy, string $sortDirection, string $searchTerm = '')
    {
        $query = Auth::user()->tasksForCurrentCompany()
            ->with([
                'lot.builder',
                'lot.supervisor',
                'taskType',
                'statusType',
                'taskNotes' => fn($q) => $q->latest('created_at')->limit(1),
                'tags' => fn($q) => $q->orderBy('tag_task.created_at', 'desc'),
            ]);

        if ($searchTerm !== '') {
            return Task::search($searchTerm)
                ->query(function ($query) use ($filters) {
                    $query->where('tasks.company_id', session('company_id'));
                    $this->applyTaskFilters($query, $filters);
                })
                ->paginate(20);
        }

        $this->applyTaskFilters($query, $filters);

        $this->applySort($query, $sortBy, $sortDirection);

        return $query->paginate(20);
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
        $companyId = session('company_id');
        $ttl       = 300;
        $filters   = [];

        foreach ($columns as $column) {
            if (empty($column['filterable'])) {
                continue;
            }

            $colKey      = $column['value'];
            $optionsQuery = Auth::user()->tasksForCurrentCompany();

            foreach ($selectedFilters as $key => $values) {
                if ($key === $colKey || empty($values)) {
                    continue;
                }
                switch ($key) {
                    case 'builder':
                        $optionsQuery->whereHas('lot.builder', fn($q)=>$q->whereIn('id',$values));
                        break;
                    case 'subdivision':
                        $optionsQuery->whereHas('lot', function($q) use ($values){
                            $q->where(function($q2) use ($values){
                                foreach ($values as $v){
                                    [$s,$p]=explode('|',$v);
                                    $q2->orWhere(fn($qq)=>$qq->where('subdivision',$s)->where('phase',$p));
                                }
                            });
                        });
                        break;
                    case 'lot_number':
                        $optionsQuery->whereHas('lot', fn($q)=>$q->whereIn('lot_number',$values));
                        break;
                    case 'task_type':
                        $optionsQuery->whereIn('task_type_id',$values);
                        break;
                    case 'status':
                        $optionsQuery->whereIn('tasks.status_type_id',$values);
                        break;
                    case 'supervisor':
                        $optionsQuery->whereHas('lot.supervisor', fn($q)=>$q->whereIn('id',$values));
                        break;
                    case 'is_extra':
                        $optionsQuery->whereIn('is_extra',$values);
                        break;
                    case 'phase':
                        $optionsQuery->whereHas('lot', fn($q)=>$q->whereIn('phase',$values));
                        break;
                }
            }

            switch ($colKey) {
                case 'builder':
                    $filters[$colKey] = Cache::remember("taskProgress:{$companyId}:builders:".md5(json_encode($selectedFilters)), $ttl, function() use ($optionsQuery){
                        return $optionsQuery->clone()->join('lots','tasks.lot_id','=','lots.id')
                            ->join('builders','lots.builder_id','=','builders.id')
                            ->distinct()
                            ->orderBy('builders.name','asc')
                            ->get(['builders.id','builders.name'])
                            ->map(fn($b)=>['value'=>$b->id,'label'=>$b->name])
                            ->toArray();
                    });
                    break;
                case 'subdivision':
                    $filters[$colKey] = Cache::remember("taskProgress:{$companyId}:subdivisions:".md5(json_encode($selectedFilters)), $ttl, function() use ($optionsQuery){
                        return $optionsQuery->clone()->join('lots','tasks.lot_id','=','lots.id')
                            ->select('lots.subdivision','lots.phase')
                            ->groupBy('lots.subdivision','lots.phase')
                            ->orderBy('lots.subdivision','asc')
                            ->get()
                            ->map(fn($i)=>['value'=>"{$i->subdivision}|{$i->phase}", 'label'=>$i->subdivision.($i->phase ? " ({$i->phase})" : '')])
                            ->toArray();
                    });
                    break;
                case 'lot_number':
                    $filters[$colKey] = Cache::remember("taskProgress:{$companyId}:lots:".md5(json_encode($selectedFilters)), $ttl, function() use ($optionsQuery){
                        return $optionsQuery->clone()->join('lots','tasks.lot_id','=','lots.id')
                            ->distinct()
                            ->orderByRaw("CAST(lots.lot_number AS UNSIGNED), lots.lot_number ASC")
                            ->pluck('lots.lot_number')
                            ->toArray();
                    });
                    break;
                case 'task_type':
                    $filters[$colKey] = Cache::remember("taskProgress:{$companyId}:taskTypes:".md5(json_encode($selectedFilters)), $ttl, function() use ($optionsQuery){
                        return $optionsQuery->clone()->join('task_types','tasks.task_type_id','=','task_types.id')
                            ->distinct()->orderBy('task_types.name','asc')
                            ->get(['task_types.id','task_types.name'])
                            ->map(fn($t)=>['value'=>$t->id,'label'=>$t->name])
                            ->toArray();
                    });
                    break;
                case 'status':
                    $filters[$colKey] = Cache::remember("taskProgress:{$companyId}:statusTypes:".md5(json_encode($selectedFilters)), $ttl, function() use ($optionsQuery){
                        return $optionsQuery->clone()->join('status_types','tasks.status_type_id','=','status_types.id')
                            ->distinct()->orderBy('status_types.name','asc')
                            ->get(['status_types.id','status_types.name'])
                            ->map(fn($s)=>['value'=>$s->id,'label'=>$s->name])
                            ->toArray();
                    });
                    break;
                case 'supervisor':
                    $filters[$colKey] = Cache::remember("taskProgress:{$companyId}:supervisors:".md5(json_encode($selectedFilters)), $ttl, function() use ($optionsQuery){
                        return $optionsQuery->clone()->join('lots','tasks.lot_id','=','lots.id')
                            ->join('supervisors','lots.supervisor_id','=','supervisors.id')
                            ->distinct()->orderBy('supervisors.name','asc')
                            ->get(['supervisors.id','supervisors.name'])
                            ->map(fn($s)=>['value'=>$s->id,'label'=>$s->name])
                            ->toArray();
                    });
                    break;
                case 'is_extra':
                    $filters[$colKey] = [
                        ['value' => 0, 'label' => 'Standard'],
                        ['value' => 1, 'label' => 'EPO'],
                    ];
                    break;
                case 'phase':
                    $filters[$colKey] = Cache::remember("taskProgress:{$companyId}:phases:".md5(json_encode($selectedFilters)), $ttl, function() use ($optionsQuery){
                        return $optionsQuery->clone()->join('lots','tasks.lot_id','=','lots.id')
                            ->select('lots.phase')
                            ->distinct()->orderBy('lots.phase','asc')
                            ->pluck('lots.phase')
                            ->toArray();
                    });
                    break;
            }
        }

        return $filters;
    }
}
