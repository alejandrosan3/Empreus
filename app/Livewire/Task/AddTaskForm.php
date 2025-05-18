<?php

namespace App\Livewire\Task;

use Livewire\Component;
use App\Models\Company;


use Flux\Flux;
use Illuminate\Support\Facades\Cache;

class AddTaskForm extends Component
{
    public $comingFromURL=false;
    
    public int $companyId;
    public $lot;
    public string $companyName;

    public $collectedFields = [];
    // Initial component variables (these will be used solely to not break livewire's parsing)
    public array $card1 = [];
    public array $card2 = [];
    public array $card3 = [];

    // Public properties sent to the card component
    public $builder_id;
    public $subdivision;
    public $lot_number;
    public $phase;
    
    public $task_type_id;
    public $order_number;
    public $price;
    public $is_extra;

    public $supervisor_id;
    public $address;
    public $scheduled_start_date;
    public $status_type_id;
    
    public $note ='';

    public function goBack()
    {
        return redirect()->route('task-progress', request()->query());
    }

    public function mount()
    {
        // grab company ID from session
        $this->companyId = session('company_id');

        // cache the company name forever under a key unique to that ID
        $this->companyName = Cache::rememberForever(
            "company_{$this->companyId}_name",
            fn() => Company::findOrFail($this->companyId)->name
        );

        $this->card1 = [
            ['label' => 'Builder', 'field' => 'builder_id', 'type' => 'select', 'source' => 'lot'],
            ['label' => 'Subdivision', 'field' => 'subdivision', 'type' => 'select', 'source' => 'lot'],
            ['label' => 'Lot', 'field' => 'lot_number', 'type' => 'select', 'source' => 'lot'],
            ['label' => 'Phase', 'field' => 'phase', 'source' => 'lot'],
        ];

        $this->card2 = [
            ['label' => 'Task Name', 'field' => 'task_type_id', 'type' => 'select', 'source' => 'task'],
            ['label' => 'Order Number', 'field' => 'order_number', 'source' => 'task'],
            ['label' => 'price', 'field' => 'price', 'source' => 'task'],
            ['label' => 'Order Type', 'field' => 'is_extra','type' => 'select', 'source' => 'task'],
        ];

        $this->card3 = [
            ['label' => 'Supervisor', 'field' => 'supervisor_id','type' => 'select' , 'source' => 'lot'],
            ['label' => 'Address', 'field' => 'address','type' => 'select' , 'source' => 'lot'],
            ['label' => 'Scheduled Start Date', 'field' => 'scheduled_start_date','type' => 'date', 'source' => 'task'],
            ['label' => 'Current Status', 'field' => 'status_type_id','type' => 'select', 'source' => 'task'],
        ];


    }

    public function save()
    {
        $lotFields  = [];
        $taskFields = [];

        $lot = \App\Models\Lot::where('builder_id',     $this->builder_id)
                      ->where('subdivision',    $this->subdivision)
                      ->where('lot_number',     $this->lot_number)
                      ->first();

        // 1) Log all incoming values (debug)
        logger()->info('[SAVE] form data', [
            'builder_id'           => $this->builder_id,
            'subdivision'          => $this->subdivision,
            'lot_number'           => $this->lot_number,
            'phase'                => $this->phase,
            'task_type_id'         => $this->task_type_id,
            'order_number'         => $this->order_number,
            'price'                => $this->price,
            'is_extra'             => $this->is_extra,
            'supervisor_id'        => $this->supervisor_id,
            'address'              => $this->address,
            'scheduled_start_date' => $this->scheduled_start_date,
            'status_type_id'       => $this->status_type_id,
        ]);

        // 2) Flatten all field definitions
        $allFields = array_merge($this->card1, $this->card2, $this->card3);

        foreach ($allFields as $def) {
            $key   = $def['field'];
            $value = $this->{$key}   ?? null;

            if ($value !== null && $value !== '') {
                if ($def['source'] === 'lot') {
                    $lotFields[$key]  = $value;
                } else {
                    $taskFields[$key] = $value;
                }
            }
        }

        // 3) Validate presence
        if (empty($lotFields) || empty($taskFields)) {
            Flux::toast(text: 'Please make sure you fill all the fields.', heading: 'wait a second 🫸', variant: 'warning');
            return;
        }

        // 4) Find or create the Lot
        $lot = \App\Models\Lot::where('builder_id',  $this->builder_id)
            ->where('subdivision',   $this->subdivision)
            ->where('lot_number',    $this->lot_number)
            ->first();
        
            if (! $lot) {
                Flux::toast(
                    text: 'Please verify builder, subdivision & lot number.',
                    heading: 'No matching lot found 🤔',
                    variant: 'warning'
                );
                return;
            }

        // 5) Create the Task (attach lot_number)
        $taskFields['lot_id'] = $lot->id;
        $taskFields['company_id'] = $this->companyId;
        $task = \App\Models\Task::create($taskFields);

        // 6) Optional note
        if (!empty($this->note)) {
            $task->taskNotes()->create([
                'description' => $this->note,
                'user_id'     => auth()->id(),
            ]);
        }

        // 7) Feedback & reset
        Flux::toast(text: 'Task created successfully.', heading: 'Success!', variant: 'success');

        // only reset the fields we just used + note
        $fieldsToReset = collect($allFields)
            ->pluck('field')
            ->push('note')
            ->all();

        $this->reset($fieldsToReset);

        $this->goBack();
    }



    public function render()
    {
        return view('livewire.task.add-task-form',[
            'companyName' => $this->companyName,    
        ])->layout('layouts.app');
    }
}
