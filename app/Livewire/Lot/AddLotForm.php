<?php

namespace App\Livewire\Lot;

use Livewire\Component;
use App\Models\Builder;
use App\Models\Company;
use App\Models\Lot;
use App\Models\Supervisor;
use Flux\Flux;

use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class AddLotForm extends Component
{
    public int    $companyId;
    public string $companyName;

    public $builders         = [];
    public $subdivisions     = [];
    public $supervisorOptions = [];
    public $phaseOptions     = [];

    public array $card1 = [];
    public array $card2 = [];
    public array $card3 = [];

    public $builder_id   = null;
    public $subdivision  = null;
    public $lot_number   = null;
    public $address      = null;
    public $supervisor_id = null;
    public $phase        = null;
    public $status_type_id = 1;
    public $num_of_floors = null;
    public $floor_area = null;

    public function rules(): array
    {
        return [
            'builder_id'   => 'required|integer',
            'subdivision'  => 'required|string',
            'lot_number'   => [
                'required', 'string',
                Rule::unique('lots', 'lot_number')->where(fn ($q) =>
                    $q->where('builder_id',  $this->builder_id)
                    ->where('subdivision', $this->subdivision)
                    ->where('company_id',  $this->companyId)
                ),
            ],
            'supervisor_id'=> 'required|integer',
        ];
    }

    protected array $messages = [
        'builder_id.required'   => 'Pick a builder.',
        'subdivision.required'  => 'Pick a subdivision.',
        'lot_number.required'   => 'Enter a lot number.',
        'lot_number.unique'     => 'This lot already exists for this builder / subdivision.',
        'address.required'      => 'Address is required.',
        'supervisor_id.required'=> 'Pick a supervisor.',
    ];

    

    public function mount(): void
    {
        $this->companyId   = session('company_id');
        $this->companyName = Cache::rememberForever(
            "company_{$this->companyId}_name",
            fn () => Company::findOrFail($this->companyId)->name
        );

        $this->builders = Cache::rememberForever(
            "builder_options.{$this->companyId}",
            fn() => Builder::whereHas('lots', fn($q) => $q->where('company_id', $this->companyId))
                          ->orderBy('name')
                          ->get()
                          ->map(fn($b) => (object)[
                              'id'   => $b->id,
                              'name' => $b->name,
                          ])
                          ->toArray()
        );

        
        $this->supervisorOptions = Cache::rememberForever(
        "supervisor_options.{$this->companyId}",
        fn () => Supervisor::where('company_id', $this->companyId)
                           ->orderBy('name')
                           ->get()
                           ->map(fn ($s) => (object)[
                               'id'   => $s->id,
                               'name' => $s->name,
                           ])
                           ->toArray()
        );

        /* 4) Card layout – mirrors your Task form style */
        $this->card1 = [
            ['label' => 'Builder',      'field' => 'builder_id',   'type' => 'select', 'options' => $this->builders],
            ['label' => 'Subdivision',  'field' => 'subdivision',  'type' => 'select', 'options' => []],
            ['label' => 'Lot Number',   'field' => 'lot_number'],
        ];

        $this->card2 = [
            ['label' => 'Phase',       'field' => 'phase'],
            ['label' => 'Address',     'field' => 'address'],
            ['label' => 'Supervisor',  'field' => 'supervisor_id','type' => 'select', 'options' => $this->supervisorOptions],
        ];

        $this->card3 = [
            ['label' => 'Number of floors', 'field' => 'num_of_floors'],
            ['label' => 'SQFT',     'field' => 'floor_area']
        ];
    }


    public function goBack()
    {
        return redirect()->route('lot-progress', request()->query());
    }

    public function updatedBuilderId($builderId): void
    {
        $this->subdivisions = Cache::rememberForever(
            "subdivision_options.{$this->companyId}.{$builderId}",
            fn () => Lot::where('company_id', $this->companyId)
                        ->where('builder_id', $builderId)
                        ->distinct('subdivision')
                        ->orderBy('subdivision')
                        ->pluck('subdivision')
                        ->map(fn ($s) => (object)[
                            'id'   => $s,
                            'name' => $s,
                        ])
                        ->toArray()
        );

        // inject into the card so <partials.cards> updates
        $this->card1[1]['options'] = $this->subdivisions;

        // reset downstream field
        $this->subdivision = null;
    } 

    public function render()
    {
        return view('livewire.lot.add-lot-form', [
            'companyName' => $this->companyName,
        ])->layout('layouts.app');
    }

    public function save()
    {
        try
        {
            $this->validate();
        } catch (ValidationException $e) 
        {
            $this->setErrorBag($e->validator->errors());

            Flux::toast(
                text: 'There are errors in the form — please fix them.',
                heading: 'Validation Failed',
                variant: 'warning'
            );
            return;
        }

        /* Create the lot */
        $lot = Lot::create([
            'company_id'    => $this->companyId,
            'builder_id'    => $this->builder_id,
            'subdivision'   => $this->subdivision,
            'lot_number'    => $this->lot_number,
            'address'       => $this->address,
            'supervisor_id' => $this->supervisor_id,
            'phase'         => $this->phase,
            'num_of_floors'   => $this->num_of_floors,
            'floor_area'     => $this->floor_area,
            'status_type_id' => $this->status_type_id
        ]);

        Flux::toast(text: 'Lot created successfully.', heading: 'Success!', variant: 'success');

        /* Reset only the bound fields (leave dropdown data untouched) */
        $this->reset([
            'builder_id', 'subdivision', 'lot_number',
            'address', 'supervisor_id', 'phase',
        ]);

        return $this->goBack();
    }

}
