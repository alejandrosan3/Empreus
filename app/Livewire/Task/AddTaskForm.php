<?php

namespace App\Livewire\Task;

use Livewire\Component;
use App\Models\Company;
use App\Models\Builder;
use App\Models\Lot;
use App\Models\TaskType;
use App\Models\StatusType;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class AddTaskForm extends Component
{
    // Context
    public int    $companyId;
    public string $companyName;

    // Dropdown option arrays
    public $builders          = [];
    public $subdivisions      = [];
    public $lots              = [];
    public $orderTypeOptions  = [];
    public $taskTypeOptions   = [];
    public $statusTypeOptions = [];

    // Card definitions
    public array $card1 = [];
    public array $card2 = [];
    public array $card3 = [];

    // Selected fields
    public $builder_id            = null;
    public $subdivision           = null;
    public $lot_number            = null;
    public $task_type_id          = null;
    public $order_number          = null;
    public $price                 = null;
    public $is_extra              = null;
    public $scheduled_start_date  = null;
    public $status_type_id        = null;
    public $note                  = '';

    // Validation rules
    public function rules(): array
    {
        // figure out the current lot’s ID for the unique check
        $lotId = Lot::where('builder_id', $this->builder_id)
                    ->where('subdivision',  $this->subdivision)
                    ->where('lot_number',   $this->lot_number)
                    ->value('id');

        return [
            'builder_id'           => 'required|integer',
            'subdivision'          => 'required|string',
            'lot_number'           => 'required|string',
            'task_type_id'         => [
                'required',
                'integer',
                Rule::unique('tasks', 'task_type_id')
                    ->where(fn($query) => $query->where('lot_id', $lotId))
            ],
            'order_number'         => 'required|string',
            'price'                => 'required|numeric|min:0',
            'is_extra'             => 'required|in:0,1',
            'status_type_id'       => 'required|integer',
        ];
    }

    // Custom validation messages
    protected array $messages = [
        'builder_id.required'  => 'Pick a builder.',
        'subdivision.required' => 'Pick a subdivision.',
        'lot_number.required'  => 'Pick a lot.',
        'price.numeric'        => 'Price must be a number.',
        'price.required'       => 'Price can\'t be empty',
        'task_type_id.required' => 'Pick a task',
        'task_type_id.unique'     => 'This task already exists for this Subdivision and Lot',
        'status_type_id'       => 'Pick a status',
        'is_extra.required'    => 'Pick the Order Type',
    ];


    public function mount()
    {
        // 1) Base context
        $this->companyId   = session('company_id');
        $this->companyName = Cache::rememberForever(
            "company_{$this->companyId}_name",
            fn() => Company::findOrFail($this->companyId)->name
        );

        // 2) Builders (forever cache)
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

        // 3) Static order types
        $this->orderTypeOptions = [
            (object)['id'=>0,'name'=>'Standard'],
            (object)['id'=>1,'name'=>'EPO'],
        ];

        // 4) Task types (forever cache)
        $this->taskTypeOptions = Cache::rememberForever(
            "task_types.{$this->companyId}",
            fn() => TaskType::orderBy('name')
                            ->get()
                            ->map(fn($t)=>(object)['id'=>$t->id,'name'=>$t->name])
                            ->toArray()
        );

        // 5) Status types (forever cache)
        $this->statusTypeOptions = Cache::rememberForever(
            "status_type_options.{$this->companyId}",
            fn() => StatusType::orderBy('name')
                              ->get()
                              ->map(fn($s)=>(object)['id'=>$s->id,'name'=>$s->name])
                              ->toArray()
        );

        // default “pending”
        $this->status_type_id = 1;

        // 6) Build minimal card definitions
        $this->card1 = [
            ['label'=>'Builder',     'field'=>'builder_id',   'type'=>'select', 'options'=>$this->builders],
            ['label'=>'Subdivision', 'field'=>'subdivision',  'type'=>'select', 'options'=>[]],
            ['label'=>'Lot',         'field'=>'lot_number',   'type'=>'select', 'options'=>[]],
        ];

        $this->card2 = [
            ['label'=>'Task Name',    'field'=>'task_type_id', 'type'=>'select', 'options'=>$this->taskTypeOptions],
            ['label'=>'Order Number', 'field'=>'order_number', 'type'=>'text'],
            ['label'=>'Price',        'field'=>'price',        'type'=>'text'],
        ];

        $this->card3 = [
            ['label'=>'Order Type',   'field'=>'is_extra',     'type'=>'select', 'options'=>$this->orderTypeOptions],
            ['label'=>'Scheduled Start Date','field'=>'scheduled_start_date','type'=>'date'],
            ['label'=>'Current Status',     'field'=>'status_type_id',      'type'=>'select', 'options'=>$this->statusTypeOptions],
        ];
    }

    // Cascade: builder → subdivisions
    public function updatedBuilderId($builderId)
    {
        
        $this->subdivisions = Cache::rememberForever(
            "subdivision_options.{$this->companyId}.{$builderId}",
            fn() => Lot::where('company_id', $this->companyId)
                       ->where('builder_id', $builderId)
                       ->distinct('subdivision')
                       ->orderBy('subdivision')
                       ->pluck('subdivision')
                       ->map(fn($s)=>(object)['id'=>$s,'name'=>$s])
                       ->toArray()
        );
        $this->card1[1]['options'] = $this->subdivisions;

        // reset downstream
        $this->subdivision  = null;
        $this->lots         = [];
        $this->card1[2]['options'] = [];
        $this->lot_number   = null;
    }

    // Cascade: subdivision → lots
    public function updatedSubdivision($sub)
    {
        $this->lots = Cache::rememberForever(
            "lot_options.{$this->companyId}.{$this->builder_id}.{$sub}",
            fn() => Lot::where('company_id',  $this->companyId)
                       ->where('builder_id',   $this->builder_id)
                       ->where('subdivision',   $sub)
                       ->orderBy('lot_number')
                       ->pluck('lot_number')
                       ->map(fn($n)=>(object)['id'=>$n,'name'=>$n])
                       ->toArray()
        );
        $this->card1[2]['options'] = $this->lots;

        // reset downstream
        $this->lot_number = null;
    }

    public function save()
    {
        try {
            $this->validate();
        } catch (ValidationException $e) {
            // 1) Push the errors into Livewire’s error bag
            $this->setErrorBag($e->validator->errors());

            // 2) Show a toast
            Flux::toast(
                text: 'There are errors in the form — please fix them.',
                heading: 'Validation Failed',
                variant: 'warning'
            );

            // 3) Bail out so we don’t proceed to creation
            return;
        }
        
        // split fields by card definitions
        $lotKeys  = collect($this->card1)->pluck('field')->all();
        $taskKeys = collect($this->card2)->pluck('field')
                         ->merge(collect($this->card3)->pluck('field'))
                         ->all();

        $lotFields  = []; $taskFields = [];
        foreach ($lotKeys  as $f) if (!empty($this->{$f}))  $lotFields[$f]  = $this->{$f};
        foreach ($taskKeys as $f) if (!empty($this->{$f}))  $taskFields[$f] = $this->{$f};

        if (empty($lotFields) || empty($taskFields)) {
            Flux::toast(text:'Please fill all fields.', heading:'Wait!', variant:'warning');
            return;
        }

        $lot = Lot::where('builder_id', $this->builder_id)
                  ->where('subdivision',  $this->subdivision)
                  ->where('lot_number',   $this->lot_number)
                  ->first();
        if (! $lot) {
            Flux::toast(text:'Verify builder, subdivision & lot.', heading:'No lot found', variant:'warning');
            return;
        }

        // attach and create
        $taskFields['lot_id']     = $lot->id;
        $taskFields['company_id'] = $this->companyId;
        $task = \App\Models\Task::create($taskFields);

        if (!empty($this->note)) {
            $task->taskNotes()->create([
                'description' => $this->note,
                'user_id'     => auth()->id(),
            ]);
        }

        Flux::toast(text:'Task created successfully.', heading:'Success!', variant:'success');

        $fieldsToReset = collect($lotKeys)
            ->merge($taskKeys)
            ->push('note')
            ->all();
        $this->reset($fieldsToReset);

        return $this->goBack();
    }

    public function goBack()
    {
        return redirect()->route('task-progress', request()->query());
    }

    public function render()
    {
        return view('livewire.task.add-task-form', [
            'companyName' => $this->companyName,
        ])->layout('layouts.app');
    }
}
