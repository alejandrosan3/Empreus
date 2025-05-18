<?php

namespace App\Livewire\Partials;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;

use App\Models\Builder;
use App\Models\Lot;


class Cards extends Component
{
    public array $items = []; // Array of title-value pairs
    public string $mode = 'view'; // Default mode is 'view', can be changed to 'edit' or 'add' as needed
    public int $companyId;

    
    public function getBuilderOptionsProperty()
    {
        return Cache::rememberForever("builder_options.{$this->companyId}", fn() =>
            Builder::select('id', 'name')
                ->whereHas('lots', fn($q) => $q->where('company_id', $this->companyId))
                ->orderBy('name')
                ->get()
        );
    }

    public function getStatusTypeOptionsProperty()
    {
        return Cache::rememberForever("status_type_options.{$this->companyId}", fn() =>
            \App\Models\StatusType::orderBy('name')
                ->select('id', 'name')
                ->get()
        );
    }

    public function getSubdivisionOptionsProperty()
    {
        return Cache::rememberForever("subdivision_options.{$this->companyId}", fn() =>
            Lot::where('company_id', $this->companyId)
                ->distinct('subdivision')
                ->orderBy('subdivision')
                ->pluck('subdivision')
                ->map(fn($s) => (object)['id' => $s, 'name' => $s])
        );
    }

    public function getLotOptionsProperty()
    {
        return Cache::rememberForever("lot_options.{$this->companyId}", fn() =>
            Lot::where('company_id', $this->companyId)
                ->select('id', 'lot_number')
                ->orderBy('lot_number')
                ->get()
        );
    }

    public function getOrderTypeOptionsProperty()
    {
        return collect([
            (object)['id' => 0, 'name' => 'Standard'],
            (object)['id' => 1, 'name' => 'EPO'],
        ]);
    }

    public function getSupervisorOptionsProperty()
    {
        return Cache::rememberForever("supervisor_options.{$this->companyId}", fn() =>
            \App\Models\Supervisor::whereHas('lots', fn($q) => $q->where('company_id', $this->companyId))
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }

    public function getAddressOptionsProperty()
    {
        return Cache::rememberForever("address_options.{$this->companyId}", fn() =>
            Lot::where('company_id', $this->companyId)
                ->distinct('address')
                ->orderBy('address')
                ->pluck('address')
                ->map(fn($a) => (object)['id' => $a, 'name' => $a])
        );
    }

    public function getTaskTypeOptionsProperty()
    {
        return Cache::rememberForever("task_types.{$this->companyId}", fn() =>
            \App\Models\TaskType::orderBy('name')
                ->select('id', 'name')
                ->get()
        );
    }


    public function render()
    {
        return view('livewire.partials.cards');
    }

    public function mount()
    {
        $this->companyId = session('company_id');
    }
}
