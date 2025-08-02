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
    public array $taskIds = [];
    public bool $bulk = false;
    public $showModal = false;
    public $selectedTeamId = null;
    public $selectedWorkerIds = [];
    public $teams = [];
    public $task;
    public $assignedWorkers;
    public $availableWorkers;

    public function mount($taskId = null)
    {
        $this->assignedWorkers = collect();
        $this->taskId = $taskId;
        
        if ($this->taskId) {
            $this->task = Task::find($taskId);
            $this->loadAssignedWorkers();
        }

        $this->loadAvailableWorkers();
        $this->loadTeams();
    }

    public function loadAssignedWorkers()
    {
         $this->assignedWorkers = $this->task ? $this->task->workers()->get() : collect();
    }

    public function loadAvailableWorkers()
    {
        $companyId = session('company_id');

        $query = User::where('role', 'worker')
            ->whereHas('companies', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            });
            
            if (! $this->bulk && $this->assignedWorkers) {
                $query->whereNotIn('id', $this->assignedWorkers->pluck('id'));
            }

            $this->availableWorkers = $query->orderBy('name')->get();
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
            $this->bulk = false;
            $this->task   = Task::find($taskId);
            $this->loadAssignedWorkers();
            $this->loadAvailableWorkers();
        }
    }

    #[On('open-bulk-assign-modal')]
    public function openBulk(array $ids)
    {
        if ($this->taskId !== null) {
            return;
        }
        $this->taskIds = $ids;
        $this->bulk = true;
        $this->showModal = true;
        $this->selectedTeamId = null;
        $this->selectedWorkerIds = [];
        $this->loadAvailableWorkers();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedTeamId = null;
        $this->selectedWorkerIds = [];
        $this->taskIds = [];
        if (! $this->bulk) {
            $this->loadAssignedWorkers();
        }
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
            $workersToAssign = $workersToAssign->unique('id');
            
            if ($this->bulk) {
                if ($workersToAssign->isEmpty()) {
                    Flux::toast(
                        variant: 'warning',
                        text: 'No workers selected.',
                        heading: 'Nothing to assign'
                    );
                    return;
                }
                foreach ($this->taskIds as $id) {
                    if ($task = Task::find($id)) {
                        $task->workers()->syncWithoutDetaching($workersToAssign->pluck('id')->toArray());
                    }
                }
            } else {
                $workersToAssign = $workersToAssign->whereNotIn('id', $this->assignedWorkers->pluck('id'));

                if ($workersToAssign->isEmpty()) {
                    Flux::toast(
                        variant: 'warning',
                        text: 'No new workers to assign.',
                        heading: 'Nothing to assign'
                    );
                    return;
                }
                foreach ($workersToAssign as $worker) {
                    $this->task->workers()->attach($worker->id);
                }
            }


            Flux::toast(
                variant: 'success',
                text: 'Workers assigned successfully',
                heading: 'All done!'
            );

            $this->closeModal();
            $this->dispatch('contentUpdated');

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
