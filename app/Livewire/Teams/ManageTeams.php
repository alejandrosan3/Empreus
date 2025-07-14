<?php

namespace App\Livewire\Teams;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class ManageTeams extends Component
{
    public $taskId;
    public $showModal = false;
    public $selectedTeamId = null;
    public $selectedWorkerIds = [];
    public $teams = [];
    public $task;
    public $assignedWorkers;
    public $availableWorkers;

    public function mount($taskId)
    {
        $this->taskId = $taskId;
        $this->task = Task::find($taskId);
        $this->loadAssignedWorkers();
        $this->loadAvailableWorkers();
        $this->loadTeams();
    }

    public function loadAssignedWorkers()
    {
        $this->assignedWorkers = $this->task->workers()->get();
    }

    public function loadAvailableWorkers()
    {
        $companyId = session('company_id');

        $this->availableWorkers = User::where('role', 'worker')
            ->whereHas('companies', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->whereNotIn('id', $this->assignedWorkers->pluck('id'))
            ->orderBy('name')
            ->get();
    }

    public function loadTeams()
    {
        $companyId = session('company_id');

        $this->teams = Team::with(['workers' => function ($query) use ($companyId) {
            $query->whereHas('companies', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }])
            ->whereHas('workers.companies', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->get();

    }

    #[On('openModal')]
    public function openModal($taskId)
    {
        if ($taskId == $this->taskId) {
            $this->showModal = true;
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedTeamId = null;
        $this->selectedWorkerIds = [];
        $this->loadAssignedWorkers();
        $this->loadAvailableWorkers();
    }

    public function assignWorkers()
    {
        try {
            $workersToAssign = collect();

            // Add selected individual workers
            if ($this->selectedWorkerIds) {
                $individualWorkers = User::whereIn('id', $this->selectedWorkerIds)->get();
                $workersToAssign = $workersToAssign->merge($individualWorkers);
            }

            // Add team workers if a team is selected
            if ($this->selectedTeamId) {
                $team = Team::find($this->selectedTeamId);
                if ($team) {
                    $workersToAssign = $workersToAssign->merge($team->workers);
                }
            }

            // Remove duplicates and already assigned workers
            $workersToAssign = $workersToAssign->unique('id')
                ->whereNotIn('id', $this->assignedWorkers->pluck('id'));

            if ($workersToAssign->isEmpty()) {
                Flux::toast(
                    variant: 'warning',
                    text: 'No new workers to assign.',
                    heading: 'Nothing to assign'
                );
                return;
            }

            // Assign workers to task
            foreach ($workersToAssign as $worker) {
                $this->task->workers()->attach($worker->id);
            }

            Flux::toast(
                variant: 'success',
                text: 'Successfully assigned ' . $workersToAssign->count() . ' worker(s) to the task.',
                heading: 'Workers Assigned'
            );

            $this->closeModal();
            $this->dispatch('workersAssigned');

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'danger',
                text: 'Failed to assign workers: ' . $e->getMessage(),
                heading: 'Assignment Failed'
            );
        }
    }

    public function removeWorker($workerId)
    {
        $this->task->workers()->detach($workerId);
        $this->loadAssignedWorkers();
        $this->loadAvailableWorkers();
        session()->flash('success', 'Worker removed from task successfully!');
    }

    public function render()
    {
        return view('livewire.teams.manage-teams');
    }
}
