<?php

namespace App\Livewire\Lot;

use Livewire\Component;
use App\Models\Builder;
use App\Models\Company;
use App\Models\Lot;
use App\Models\Supervisor;
use Flux\Flux;
use App\Services\CacheInvalidationService;

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

    public bool $showSubdivisionInput = false;
    public string $newSubdivision = '';
    public string $subdToggleTooltip = 'Create New Subdivision';
    public string $subdToggleIcon = 'plus';

    public bool $showSupervisorInput = false;
    public string $newSupervisor = '';
    public string $supervisorToggleTooltip = 'Create New Subdivision';
    public string $supervisorToggleIcon = 'plus';

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
            'supervisor_id'=> $this->showSupervisorInput ? 'nullable|integer' : 'required|integer',
            'newSupervisor' => $this->showSupervisorInput ? 'required|string|max:255' : 'nullable',
        ];
    }

    protected array $messages = [
        'builder_id.required'   => 'Pick a builder.',
        'subdivision.required'  => 'Pick a subdivision.',
        'lot_number.required'   => 'Enter a lot number.',
        'lot_number.unique'     => 'This lot already exists for this builder / subdivision.',
        'address.required'      => 'Address is required.',
        'supervisor_id.required'=> 'Pick a supervisor.',
        'newSupervisor.required'=> 'Enter supervisor name.',
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
        fn () => Supervisor::orderBy('name')
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

    public function toggleSubdivisionInput(): void
    {
        $this->showSubdivisionInput = ! $this->showSubdivisionInput;
        if ($this->showSubdivisionInput) {
            $this->subdivision = null;
            $this->subdToggleTooltip = 'Select Subdivision';
            $this->subdToggleIcon = 'x-mark';
        } else {
            $this->newSubdivision = '';
            $this->subdToggleTooltip = 'Create New Subdivision';
            $this->subdToggleIcon = 'plus';
        }
    }

    public function toggleSupervisorInput(): void
    {
        $this->showSupervisorInput = ! $this->showSupervisorInput;
        if ($this->showSupervisorInput) {
            $this->supervisor_id = null;
            $this->supervisorToggleTooltip = 'Create New Supervisor';
            $this->supervisorToggleIcon = 'plus';
        } else {
            $this->newSupervisor = '';
            $this->supervisorToggleTooltip = 'Create New Supervisor';
            $this->supervisorToggleIcon = 'plus';
        }
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
        $this->showSubdivisionInput = false;
        $this->newSubdivision = '';
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
            if ($this->showSubdivisionInput && !empty($this->newSubdivision)) {
                $this->subdivision = $this->newSubdivision;
            }

            if ($this->showSupervisorInput && !empty($this->newSupervisor)) {
                // validation will ensure name is provided
            }

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

        $subdivisionExists = Lot::where('company_id', $this->companyId)
            ->where('builder_id', $this->builder_id)
            ->where('subdivision', $this->subdivision)
            ->exists();

        if ($this->showSupervisorInput && !empty($this->newSupervisor)) {
            $supervisor = Supervisor::firstOrCreate(['name' => $this->newSupervisor]);
            if ($supervisor->wasRecentlyCreated) {
                CacheInvalidationService::forNewSupervisor($this->companyId);
            }
            $this->supervisor_id = $supervisor->id;
        }

        /* Create the lot */
        $lot = Lot::create([
            'company_id'    => $this->companyId,
            'builder_id'    => $this->builder_id,
            'subdivision'   => !empty($this->newSubdivision)
                                ? $this->newSubdivision
                                : $this->subdivision,
            'lot_number'    => $this->lot_number,
            'address'       => $this->address,
            'supervisor_id' => $this->supervisor_id,
            'phase'         => $this->phase,
            'num_of_floors' => $this->num_of_floors,
            'floor_area'    => $this->floor_area,
            'status_type_id'=> $this->status_type_id,
        ]);

        CacheInvalidationService::forNewLot(
            $this->companyId,
            $this->builder_id,
            $this->subdivision
        );

        if (! $subdivisionExists) {
            CacheInvalidationService::forNewSubdivision($this->companyId, $this->builder_id);
        }

        Flux::toast(text: 'Lot created successfully.', heading: 'Success!', variant: 'success');

        /* Reset only the bound fields (leave dropdown data untouched) */
        $this->reset([
            'builder_id', 'subdivision', 'lot_number',
            'address', 'supervisor_id', 'phase',
            'newSubdivision', 'showSubdivisionInput',
            'newSupervisor', 'showSupervisorInput',
        ]);

        return $this->goBack();
    }

}
