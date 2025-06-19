<?php

namespace App\Livewire\Lot;

use Livewire\Component;
use App\Models\Lot;
use App\Models\Supervisor;
use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class DuplicateLotForm extends Component
{
    public bool $showModal = false;

    public int $companyId;
    public string $companyName;

    public $supervisorOptions = [];

    public array $card1 = [];
    public array $card2 = [];

    public $lot_id;
    public $builder_id;
    public $subdivision;

    public $lot_number;
    public $phase;
    public $address;
    public $supervisor_id;

    public $num_of_floors;
    public $floor_area;
    public $status_type_id;

    public function rules(): array
    {
        return [
            'lot_number'   => 'required|string',
            'phase'        => 'nullable|string',
            'address'      => 'nullable|string',
            'supervisor_id'=> 'required|integer',
        ];
    }

    protected array $messages = [
        'lot_number.required'   => 'Lot number required',
        'supervisor_id.required'=> 'Select supervisor',
    ];

    public function mount(): void
    {
        $this->companyId = session('company_id');
        $this->companyName = Cache::rememberForever(
            "company_{$this->companyId}_name",
            fn () => \App\Models\Company::findOrFail($this->companyId)->name
        );

        $this->supervisorOptions = Cache::rememberForever(
            "supervisor_options.{$this->companyId}",
            fn () => Supervisor::orderBy('name')
                               ->get()
                               ->map(fn ($s) => (object)['id' => $s->id, 'name' => $s->name])
                               ->toArray()
        );

        $this->card2 = [
            ['label' => 'Lot Number',  'field' => 'lot_number'],
            ['label' => 'Phase',       'field' => 'phase'],
            ['label' => 'Address',     'field' => 'address'],
            ['label' => 'Supervisor',  'field' => 'supervisor_id', 'type' => 'select', 'options' => $this->supervisorOptions],
        ];
    }

    #[\Livewire\Attributes\On('open-duplicate-lot')]
    public function open(int $lotId): void
    {
        $lot = Lot::with('builder','supervisor')->findOrFail($lotId);

        $this->lot_id = $lotId;
        $this->builder_id = $lot->builder_id;
        $this->subdivision = $lot->subdivision;
        $this->num_of_floors = $lot->num_of_floors;
        $this->floor_area = $lot->floor_area;
        $this->status_type_id = $lot->status_type_id;

        $this->card1 = [
            ['label' => 'Builder',     'value' => $lot->builder->name],
            ['label' => 'Subdivision', 'value' => $lot->subdivision],
        ];

        $this->lot_number = $lot->lot_number;
        $this->phase = $lot->phase;
        $this->address = $lot->address;
        $this->supervisor_id = $lot->supervisor_id;

        $this->showModal = true;
    }

    public function save()
    {
        try {
            $this->validate();
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
            Flux::toast(text: 'Fix errors before continuing.', heading: 'Validation', variant: 'warning');
            return;
        }

        Lot::create([
            'company_id'    => $this->companyId,
            'builder_id'    => $this->builder_id,
            'subdivision'   => $this->subdivision,
            'lot_number'    => $this->lot_number,
            'address'       => $this->address,
            'supervisor_id' => $this->supervisor_id,
            'phase'         => $this->phase,
            'num_of_floors' => $this->num_of_floors,
            'floor_area'    => $this->floor_area,
            'status_type_id'=> $this->status_type_id,
        ]);

        \App\Services\CacheInvalidationService::forNewLot($this->companyId, $this->builder_id, $this->subdivision);

        Flux::toast(text: 'Lot duplicated.', heading: 'Success', variant: 'success');
        $this->reset(['showModal', 'lot_number', 'phase', 'address', 'supervisor_id']);
        $this->dispatch('contentUpdated');
    }

    public function render()
    {
        return view('livewire.lot.duplicate-lot-form');
    }
}
