<?php

namespace App\Livewire\Workers;

use Livewire\Component;
use App\Models\Team;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\On;

class ManageWorkerTeams extends Component
{
    public bool $showModal = false;

    /**
     * IDs of workers being assigned.
     *
     * @var array<int>
     */
    public array $workerIds = [];

    /**
     * Selected team IDs from the dropdown.
     *
     * @var array<int>
     */
    public array $teamIds = [];

    /**
     * Available teams for the current company.
     */
    public $teams = [];

    public function mount(): void
    {
        $this->loadTeams();
    }

    public function loadTeams(): void
    {
        $companyId   = session('company_id');
        $this->teams = Team::whereHas('workers.companies', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->get();
    }

    #[On('open-worker-team-modal')]
    public function open(array $ids): void
    {
        $this->reset('teamIds');
        $this->workerIds = $ids;
        $this->showModal = true;
    }

    public function assign(): void
    {
        if (empty($this->teamIds)) {
            $this->showModal = false;
            return;
        }

        foreach ($this->workerIds as $id) {
            if ($worker = User::find($id)) {
                $worker->teams()->syncWithoutDetaching($this->teamIds);
            }
        }

        Flux::toast(
            variant: 'success',
            text: 'Teams assigned successfully',
            heading: 'All done!'
        );

        $this->showModal = false;
        $this->dispatch('contentUpdated');
    }

    public function render()
    {
        return view('livewire.workers.manage-worker-teams');
    }
}
