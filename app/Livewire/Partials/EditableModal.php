<?php

namespace App\Livewire\Partials;

use Livewire\Component;
use Flux\Flux;
use Carbon\Carbon;

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


        /**
     * Show a toast message indicating that the field is not editable.
     */
    public function notEditableActions()
    {
        Flux::toast(variant: 'warning', text:"This field can't be edited.", heading:'Stop right there! 🫸');
    }

    public function editField($fieldId, $currentValue, $fieldType = 'input', $optionKey = null, $modelClass = null, $modelAttribute = 'content', $editable=true)
    {
        if(!$editable){
            $this->notEditableActions();
            return;
        }
        $this->selectOptions = match($optionKey) {    
            'builders'     => \App\Models\Builder::pluck('name','id')->toArray(),
            'taskTypes'    => \App\Models\TaskType::pluck('name','id')->toArray(),
            'statusTypes'  => \App\Models\StatusType::pluck('name','id')->toArray(),
            'supervisors'  => \App\Models\Supervisor::pluck('name','id')->toArray(),
            'subdivisions' => \App\Models\Lot::distinct()->pluck('subdivision')->toArray(),
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

    public function save()
    {
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
                    
        $record->update([$this->modelAttribute => $this->fieldValue]);

        $this->showModal = false;

        Flux::toast(text: 'Field updated successfully.', variant: 'success');

        $this->dispatch('contentUpdated');
        return;
    }

    public function render()
    {   
        return view('livewire.partials.editable-modal');
    }

    public function mount()
    {
        if ($this->fieldType === 'date' && !empty($this->fieldValue)) {
            $currentValue = Carbon::parse($currentValue)->format('Y-m-d H:i:s');
        }
    }
}
