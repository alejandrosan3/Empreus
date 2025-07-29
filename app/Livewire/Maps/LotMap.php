<?php

namespace App\Livewire\Maps;

use App\Models\Lot;
use Livewire\Component;
use Flux\DateRange;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;

/**
 * Component showing a map with clustering for lots that have confirmed tasks.
 */
class LotMap extends Component
{
    /**
     * Array of lots (id, address, subdivision).
     */
    public array $lots;
    public $subdivisions= [];

    #[Reactive]
    public DateRange $range;

    public bool $showLegend = false;

    public function mount(): void
    {
        if (!isset($this->range)) {
            $this->range = DateRange::thisWeek();
        }

        $this->loadLots();
    }

    private function loadLots(): void
    {
        $start = $this->range->copy()->start();
        $end   = $this->range->copy()->end();

        $this->lots = Lot::query()
                ->whereHas('tasks', function ($q) use ($start, $end) {
                    $q->whereBetween('scheduled_start_date', [$start, $end])
                    ->whereHas('statusType', fn($q2) => $q2->whereIn('identifier', ['confirmed', 'in_progress']));
                })
                ->get(['id', 'address', 'subdivision'])
                ->toArray();
    }

    public function render()
    {
        $this->subdivisions = collect($this->lots)
            ->pluck('subdivision')
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        return view('livewire.maps.lot-map')->layout('layouts.app',[
                'showFilters' => true,
            ]);
    }
}
