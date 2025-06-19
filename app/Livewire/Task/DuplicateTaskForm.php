<?php

namespace App\Livewire\Task;

use Livewire\Component;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\StatusType;
use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class DuplicateTaskForm extends Component
{
    public bool $showModal = false;

    public int $companyId;
    public string $companyName;

    public $orderTypeOptions = [];
    public $taskTypeOptions = [];
    public $statusTypeOptions = [];

    public array $card1 = [];
    public array $card2 = [];
    public array $card3 = [];

    public $task_id;
    public $lot_id;

    public $task_type_id;
    public $order_number;
    public $price;
    public $is_extra;
    public $scheduled_start_date;
    public $status_type_id;
    public $note = '';

    public function rules(): array
    {
        return [
            'task_type_id' => 'required|integer',
            'order_number' => 'required|string',
            'price'        => 'required|numeric|min:0',
            'is_extra'     => 'required|in:0,1',
            'status_type_id'=> 'required|integer',
        ];
    }

    protected array $messages = [
        'order_number.required' => 'Order number required',
        'price.required'        => 'Price required',
    ];

    public function mount(): void
    {
        $this->companyId = session('company_id');
        $this->companyName = Cache::rememberForever(
            "company_{$this->companyId}_name",
            fn () => \App\Models\Company::findOrFail($this->companyId)->name
        );

        $this->orderTypeOptions = [
            (object)['id' => 0, 'name' => 'Standard'],
            (object)['id' => 1, 'name' => 'EPO'],
        ];

        $this->taskTypeOptions = Cache::rememberForever(
            "task_types.{$this->companyId}",
            fn () => TaskType::orderBy('name')
                            ->get()
                            ->map(fn ($t) => (object)['id' => $t->id, 'name' => $t->name])
                            ->toArray()
        );

        $this->statusTypeOptions = Cache::rememberForever(
            "status_type_options.{$this->companyId}",
            fn () => StatusType::orderBy('name')
                              ->get()
                              ->map(fn ($s) => (object)['id' => $s->id, 'name' => $s->name])
                              ->toArray()
        );

        $this->card2 = [
            ['label' => 'Task Name',    'field' => 'task_type_id', 'type' => 'select', 'options' => $this->taskTypeOptions],
            ['label' => 'Order Number', 'field' => 'order_number'],
            ['label' => 'Price',        'field' => 'price'],
        ];

        $this->card3 = [
            ['label' => 'Order Type',          'field' => 'is_extra',              'type' => 'select', 'options' => $this->orderTypeOptions],
            ['label' => 'Scheduled Start Date','field' => 'scheduled_start_date',   'type' => 'date'],
            ['label' => 'Current Status',      'field' => 'status_type_id',        'type' => 'select', 'options' => $this->statusTypeOptions],
        ];
    }

    #[\Livewire\Attributes\On('open-duplicate-task')]
    public function open(int $taskId): void
    {
        $task = Task::with('lot.builder')->findOrFail($taskId);

        $this->task_id = $taskId;
        $this->lot_id = $task->lot_id;

        $this->card1 = [
            ['label' => 'Builder',     'value' => $task->lot->builder->name],
            ['label' => 'Subdivision', 'value' => $task->lot->subdivision],
            ['label' => 'Lot',         'value' => $task->lot->lot_number],
        ];

        $this->task_type_id = $task->task_type_id;
        $this->order_number = $task->order_number;
        $this->price = $task->price;
        $this->is_extra = $task->is_extra;
        $this->scheduled_start_date = $task->scheduled_start_date;
        $this->status_type_id = $task->status_type_id;
        $this->note = '';

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

        $task = Task::create([
            'lot_id'               => $this->lot_id,
            'company_id'           => $this->companyId,
            'task_type_id'         => $this->task_type_id,
            'order_number'         => $this->order_number,
            'price'                => $this->price,
            'is_extra'             => $this->is_extra,
            'scheduled_start_date' => $this->scheduled_start_date,
            'status_type_id'       => $this->status_type_id,
        ]);

        if (!empty($this->note)) {
            $task->taskNotes()->create([
                'description' => $this->note,
                'user_id'     => auth()->id(),
            ]);
        }

        Flux::toast(text: 'Task duplicated.', heading: 'Success', variant: 'success');
        $this->reset(['showModal', 'task_type_id', 'order_number', 'price', 'is_extra', 'scheduled_start_date', 'status_type_id', 'note']);
        $this->dispatch('contentUpdated');
    }

    public function render()
    {
        return view('livewire.task.duplicate-task-form');
    }
}
