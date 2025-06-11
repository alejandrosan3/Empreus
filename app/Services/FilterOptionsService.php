<?php

namespace App\Services;

use App\Models\Lot;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class FilterOptionsService
{
    public function taskOptions(array $builders = [], array $subdivisions = [], array $lots = []): array
    {
        $query = Auth::user()->tasksForCurrentCompany();

        $query->filterByBuilders($builders)
            ->filterBySubdivisions($subdivisions)
            ->filterByLots($lots);

        $buildersList = $query->clone()
            ->join('lots', 'tasks.lot_id', '=', 'lots.id')
            ->join('builders', 'lots.builder_id', '=', 'builders.id')
            ->distinct()
            ->orderBy('builders.name')
            ->pluck('builders.name')
            ->toArray();

        $subdivisionList = $query->clone()
            ->join('lots', 'tasks.lot_id', '=', 'lots.id')
            ->select('lots.subdivision', 'lots.phase')
            ->groupBy('lots.subdivision', 'lots.phase')
            ->orderBy('lots.subdivision')
            ->get()
            ->map(fn ($item) => [
                'value' => "{$item->subdivision}|{$item->phase}",
                'label' => $item->subdivision . ($item->phase ? " ({$item->phase})" : ''),
            ])
            ->toArray();

        $lotList = $query->clone()
            ->join('lots', 'tasks.lot_id', '=', 'lots.id')
            ->distinct()
            ->orderBy('lots.lot_number')
            ->pluck('lots.lot_number')
            ->toArray();

        return [
            'builders' => $buildersList,
            'subdivisions' => $subdivisionList,
            'lots' => $lotList,
        ];
    }

    public function lotOptions(array $builders = [], array $subdivisions = [], array $lots = []): array
    {
        $query = Auth::user()->lotsForCurrentCompany();

        $query->filterByBuilders($builders)
            ->filterBySubdivisions($subdivisions)
            ->filterByLots($lots);

        $buildersList = $query->clone()
            ->join('builders', 'lots.builder_id', '=', 'builders.id')
            ->distinct()
            ->orderBy('builders.name')
            ->pluck('builders.name')
            ->toArray();

        $subdivisionList = $query->clone()
            ->select('subdivision', 'phase')
            ->groupBy('subdivision', 'phase')
            ->orderBy('subdivision')
            ->get()
            ->map(fn ($item) => [
                'value' => $item['subdivision'] . '|' . $item['phase'],
                'label' => $item['subdivision'] . ($item['phase'] ? " ({$item['phase']})" : ''),
            ])
            ->toArray();

        $lotList = $query->clone()
            ->distinct()
            ->orderBy('lot_number')
            ->pluck('lot_number')
            ->toArray();

        return [
            'builders' => $buildersList,
            'subdivisions' => $subdivisionList,
            'lots' => $lotList,
        ];
    }
}
