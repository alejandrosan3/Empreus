<?php

namespace App\Livewire\Partials;

use Livewire\Component;
use Flux\Flux;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class EditableModal extends Component
{
    //public $showModal = false;
    public $fieldValue = '';
    public $fieldId;
    public $fieldType = 'input'; // 'select' or any other type using https://fluxui.dev as reference
    public $selectOptions = [];  // Populated dynamically
    public $modelClass;
    public $modelAttribute;
    public $editable;
    // Needed for the select field loading.
    public bool $createNewNote = false; // used to decide "edit latest" vs "add new" for Task Notes
    // Add back these option lists used by optionKey in editField()
    public array $builders     = [];
    public array $taskTypes    = [];
    public array $statusTypes  = [];
    public array $supervisors  = [];
    public array $subdivisions = [];

    protected $listeners = ['editField'];

    public int $companyId;


    /**
     * Show a toast message indicating that the field is not editable.
     */
    public function notEditableActions()
    {
        Flux::toast(variant: 'warning', text:"This field can't be edited.", heading:'Stop right there! 🫸');
    }

    public function editField($fieldId, $currentValue, $fieldType = 'input', $optionKey = null, $modelClass = null, $modelAttribute = 'content', $editable=true, $createNewNote = false)
    {
        if(!$editable){
            $this->notEditableActions();
            return;
        }

        // Temporary: log payload for debugging the Status Type column
        \Log::debug('EditableModal@editField payload', [
            'fieldId'        => $fieldId,
            'currentValue'   => $currentValue,
            'fieldType'      => $fieldType,
            'optionKey'      => $optionKey,
            'modelClass'     => $modelClass,
            'modelAttribute' => $modelAttribute,
            'editable'       => $editable,
        ]);


        $this->selectOptions = match($optionKey) {
            'builders'     => $this->builders,
            'taskTypes'    => $this->taskTypes,
            'statusTypes'  => $this->statusTypes,
            'supervisors'  => $this->supervisors,
            'subdivisions' => $this->subdivisions,
            'is_extra'     => ['0' => 'Standard','1' => 'EPO'],
            default        => [],
        };

        $this->fieldId        = $fieldId;
        $this->fieldValue     = $currentValue;
        $this->fieldType      = $fieldType;
        $this->modelClass     = $modelClass;
        $this->modelAttribute = $modelAttribute;
        $this->editable       = $editable;
        $this->createNewNote  = (bool) $createNewNote;

        if ($fieldType === 'date') {
            $this->fieldValue = $currentValue
                ? Carbon::parse($currentValue)->format('Y-m-d')
                : '';
        }

        Flux::modal('dbl-click-modal')->show();
    }

    /**
     * Define rules dynamically based on which attribute we're editing.
     */
    protected function rules(): array
    {
        return [
            'fieldValue' => match($this->modelAttribute) {
                // mandatory selects
                'builder_id'       => 'required|exists:builders,id',
                'task_type_id'     => 'required|exists:task_types,id',
                'subdivision'      => 'required|string',
                'is_extra'         => 'required|in:0,1',

                // optional selects
                'status_type_id'   => 'required|exists:status_types,id',
                'supervisor_id'    => 'nullable|exists:supervisors,id',

                // inputs
                'lot_number'       => 'required|string|min:1',
                'address'          => 'nullable|string',
                'description'      => 'nullable|string',
                'name'             => 'required|string',
                'email'            => 'required|email',

                // numbers
                'num_of_floors'    => 'required|integer',
                'floor_area'       => 'required|numeric',
                'price'            => 'required|numeric',
                'crew_pay'         => 'nullable|numeric|min:0',

                // dates (optional—you can skip if you’re certain the picker always gives valid Y-m-d)
                'scheduled_start_date' => 'nullable|date',
                'ended_at'            => 'nullable|date',
                "started_at"           => 'nullable|date',
                "scheduled_end_date"   => 'nullable|date',
                'phase'               => 'nullable|string',

                // catch-all sfor other case
                default            => 'nullable|string',

            },
        ];
    }

    /**
     * Define validation messages dynamically based on which attribute we're editing.
     */
    protected function messages(): array
    {
        return [
            'fieldValue.required' => 'This field can’t be empty.',
            'fieldValue.exists'   => 'Please pick a valid option.',
            'fieldValue.in'       => 'Invalid choice selected.',
            'fieldValue.string'   => 'Must be text.',
            'fieldValue.min'      => 'Must be at least :min character.',
            'fieldValue.integer'  => 'Must be a whole number.',
            'fieldValue.numeric'  => 'Must be a valid number.',
            'fieldValue.date'     => 'Must be a valid date.',
        ];
    }

    public function save()
    {
        $this->validate($this->rules(), $this->messages());

        $allowedModels = [
            \App\Models\Builder::class,
            \App\Models\Subdivision::class,
            \App\Models\Lot::class,
            \App\Models\Task::class,
            \App\Models\TaskType::class,
            \App\Models\StatusType::class,
            \App\Models\Supervisor::class,
            \App\Models\TaskNote::class,
            \App\Models\Team::class,
            \App\Models\User::class,
        ];

        if (!in_array($this->modelClass, $allowedModels)) {
            Flux::toast(heading: "Warning", text:'Invalid model specified.', variant: "danger");
            return;
        }

        $model = $this->modelClass;

        // Notes: match subdivision-like toggle behavior (edit latest vs add new)
        if ($model == \App\Models\TaskNote::class) {
            try {
                if ($this->createNewNote) {
                    \App\Models\TaskNote::create([
                        'task_id'     => $this->fieldId,   // fieldId is Task ID
                        'description' => $this->fieldValue,
                    ]);
                    Flux::toast(text: 'Note added successfully.', variant: 'success');
                } else {
                    $note = \App\Models\TaskNote::where('task_id', $this->fieldId)->latest('created_at')->first();
                    if ($note) {
                        $note->update(['description' => $this->fieldValue]);
                        Flux::toast(text: 'Note updated successfully.', variant: 'success');
                    } else {
                        \App\Models\TaskNote::create([
                            'task_id'     => $this->fieldId,
                            'description' => $this->fieldValue,
                        ]);
                        Flux::toast(text: 'Note added successfully.', variant: 'success');
                    }
                }

                Flux::modal('dbl-click-modal')->close();
                $this->dispatch('contentUpdated');

                $this->reset([
                    'fieldValue',
                    'fieldId',
                    'fieldType',
                    'selectOptions',
                    'modelClass',
                    'modelAttribute',
                    'editable',
                    'createNewNote',
                ]);
                return;
            } catch (\Throwable $e) {
                Flux::toast(text: 'Failed to save note.', variant: 'danger');
                return;
            }
        }

        $record = $model::findOrFail($this->fieldId);

        // transform empty String to null for select fields
        $value = $this->fieldValue;
        if ($this->fieldType === 'select' && $value === '') {
            $value = null;
        }

        // Optional numbers: blank → null
        if ($this->modelAttribute === 'crew_pay' && $value === '') {
            $value = null;
        }

        // Dates: blank → null, else leave as-is (YYYY-MM-DD)
        if ($this->fieldType === 'date') {
            $value = $value ?: null;
        }

        $record->update([$this->modelAttribute => $value]);

        Flux::modal('dbl-click-modal')->close();

        Flux::toast(text: 'Field updated successfully.', variant: 'success');

        $this->dispatch('contentUpdated');

        $this->reset([
            'fieldValue',
            'fieldId',
            'fieldType',
            'selectOptions',
            'modelClass',
            'modelAttribute',
            'editable',
            'createNewNote',
        ]);
        return;
    }

    // Put the toggle logic in the component so Blade stays simple and consistent with subdivision UX
    public function toggleCreateNewNote(): void
    {
        $this->createNewNote = ! $this->createNewNote;

        // Clear the input when switching to "Add new" (same behavior you expect when adding)
        if ($this->createNewNote) {
            $this->fieldValue = '';
        }
    }

    public function render()
    {
        return view('livewire.partials.editable-modal');
    }

    public function mount()
    {
        $this->companyId = session('company_id');

        if ($this->fieldType === 'date' && !empty($this->fieldValue)) {
            $currentValue = Carbon::parse($currentValue)->format('Y-m-d H:i:s');
        }

        $this->companyId = session('company_id');

        $this->builders     = Cache::remember(
            "builders.{$this->companyId}",
            3600,
            fn() => \App\Models\Builder::whereHas('lots',fn($q)=> $q->where('company_id',$this->companyId))
                                                ->pluck('name','id')
                                                ->toArray()
        );

        $this->supervisors  = Cache::remember(
            "supervisors.{$this->companyId}",
            3600,
            fn() => \App\Models\Supervisor::whereHas('lots',fn($q)=> $q->where('company_id',$this->companyId))
                                                    ->pluck('name','id')
                                                    ->toArray()
        );

        $this->subdivisions = Cache::remember(
            "subdivisions.{$this->companyId}",
            3600,
            fn() => \App\Models\Lot::where('company_id',$this->companyId)
                                             ->distinct()
                                             ->pluck('subdivision')
                                             ->toArray()
        );


        $this->taskTypes    = Cache::remember(
            "taskTypes.{$this->companyId}",
            3600,
            fn() => \App\Models\TaskType::pluck('name','id')->toArray()
        );

        $this->statusTypes  = Cache::remember(
            "statusTypes.{$this->companyId}",
            3600,
            fn() => \App\Models\StatusType::pluck('name','id')->toArray()
        );
    }
}
