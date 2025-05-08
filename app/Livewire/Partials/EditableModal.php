<?php

namespace App\Livewire\Partials;

use Livewire\Component;
use Flux\Flux;
use Carbon\Carbon;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Cache;

class EditableModal extends Component
{
    public $showModal = false;
    public $fieldValue = '';
    public $fieldId;
    public $fieldType = 'input'; // 'select' or any other type using https://fluxui.dev as reference
    public $selectOptions = [];  // Populated dynamically
    public $modelClass;
    public $modelAttribute;
    public $editable;

    // Needed for the select field loading.
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

    public function editField($fieldId, $currentValue, $fieldType = 'input', $optionKey = null, $modelClass = null, $modelAttribute = 'content', $editable=true)
    { 
        $companyId = session('company_id');
        if(!$editable){
            $this->notEditableActions();
            return;
        }
        $this->selectOptions = match($optionKey) {    
            'builders'     =>  $this->builders,
            'taskTypes'    =>  $this->taskTypes,
            'statusTypes'  =>  $this->statusTypes,
            'supervisors'  =>  $this->supervisors,
            'subdivisions' =>  $this->subdivisions,
            'is_extra'     => ['0' => 'Standard','1' => 'EPO'],
            default        => [],
        };

            $this->fieldId = $fieldId;
            $this->fieldValue = $currentValue;
            $this->fieldType = $fieldType;
            $this->modelClass = $modelClass;
            $this->modelAttribute = $modelAttribute;
            $this->editable = $editable;

            if ($fieldType === 'date') {
                $this->fieldValue = $currentValue
                    ? Carbon::parse($currentValue)->format('Y-m-d')
                    : '';
            }
        

            $this->showModal = true;
        
        


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

                // numbers
                'num_of_floors'    => 'required|integer',
                'floor_area'       => 'required|numeric',

                // dates (optional—you can skip if you’re certain the picker always gives valid Y-m-d)
                'scheduled_start_date',
                'ended_at',
                "started_at",
                "scheduled_end_date"         => 'nullable|date',

                default            => 'required',
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
        $this->validate(
            $this->rules(),
            $this->messages()
        );
        $allowedModels = [
            \App\Models\Builder::class,
            \App\Models\Subdivision::class,
            \App\Models\Lot::class,
            \App\Models\Task::class,
            \App\Models\TaskType::class,
            \App\Models\StatusType::class,
            \App\Models\Supervisor::class,
            \App\Models\TaskNote::class
        ];
    
        if (!in_array($this->modelClass, $allowedModels)) {
            Flux::toast(heading: "Warning" , text:'Invalid model specified.', variant: "danger" );
            return;
        }
        $model = $this->modelClass;
        $record = $model::findOrFail($this->fieldId);

        // transform empty String to null for select fields
        $value = $this->fieldValue;
        if ($this->fieldType === 'select' && $value === '') {
            $value = null;
        }

        // Dates: blank → null, else leave as-is (YYYY-MM-DD)
        if ($this->fieldType === 'date') {
            $value = $value ?: null;
        }
        
        if ($model == \App\Models\TaskNote::class) {
            $note = $model::where('task_id', $this->fieldId)
                        ->latest('created_at')
                        ->first();
            if ($note) {
                $note->update(['description' => $this->fieldValue]);
            } else {
                $model::create([
                        'task_id'     => $this->fieldId,
                        'description' => $this->fieldValue,
                ]);
            }
        }
        $record->update([ $this->modelAttribute => $value,]);

        $this->showModal = false;

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
          ]);
        return;
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
