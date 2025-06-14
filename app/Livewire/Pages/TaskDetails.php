<?php
namespace App\Livewire\Pages;


use Livewire\Component;
use App\Models\Task;


class TaskDetails extends Component
{
    public $task;
    public $taskTypes=[];
    public $comingFromURL=false;


    public function goBack(){
        if($this->comingFromURL){
            return redirect()->route('task-progress');
        }else{
            $this->dispatch('back-button-press',selectedTask: null);
        }
    }
    public function mount($task_id = null, $task = null)
    {
        $this->comingFromURL= $task_id? true : false;
        if ($task) {
            // Use the passed lot object directly
            $this->task = $task;
        } elseif ($task_id) {
            // Fetch the lot using the ID from the route
            $this->task = Task::with(['lot.builder', 'lot.supervisor', 'taskType', 'statusType', 'workers'])->findOrFail($task_id);
        } else {
            // Throw an exception if neither parameter is provided
            throw new \Exception('No task provided');
        }

        // Group tasks by type dynamically
        $this->taskTypes = [
            'standard' => $this->task->lot->tasks->where('is_extra', false),
            'extra' => $this->task->lot->tasks->where('is_extra', true),
        ];

    }

    public function render()
    {
        return view('livewire.pages.task-details');
    }
}
