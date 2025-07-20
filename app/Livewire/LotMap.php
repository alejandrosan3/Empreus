<?php

namespace App\Livewire;

use App\Models\Lot;
use Livewire\Component;

/**
 * Component showing a map with clustering for lots that have confirmed tasks.
 */
class LotMap extends Component
{
    /**
     * Array of lots (id, address, subdivision).
     */
    public array $lots = [];

    public function mount(): void
    {
        $this->lots = Lot::query()
            ->whereHas('tasks.statusType', fn($q) => $q->where('identifier', 'confirmed'))
            ->get(['id', 'address', 'subdivision'])
            ->toArray();
    }

    public function render()
    {
        $subdivisions = collect($this->lots)
            ->pluck('subdivision')
            ->unique()
            ->values();

        return view('livewire.maps.lot-map', [
            'subdivisions' => $subdivisions,
        ]);
    }
}
