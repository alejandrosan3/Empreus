<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use App\Models\Lot;
use App\Models\Task;
use App\Models\Team;
use App\Models\StatusType;
use Carbon\Carbon;

class Dashboard extends Component
{
    public $activeLots = 0;
    public $pendingTasks = 0;
    public $activeTeams = 0;
    public $completedToday = 0;

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        $companyId = session('company_id');
        
        if (!$companyId) {
            return;
        }

        // Active Lots (lots with tasks in progress or confirmed)
        $this->activeLots = Lot::where('company_id', $companyId)
            ->whereHas('tasks', function ($query) {
                $query->whereHas('statusType', function ($q) {
                    $q->whereIn('identifier', ['confirmed', 'in_progress']);
                });
            })
            ->count();

        // Pending Tasks (tasks that are pending or confirmed)
        $this->pendingTasks = Task::where('company_id', $companyId)
            ->whereHas('statusType', function ($query) {
                $query->whereIn('identifier', ['pending', 'confirmed']);
            })
            ->count();

        // Active Teams (teams that have workers)
        $this->activeTeams = Team::whereHas('workers')
            ->count();

        // Completed Today
        $this->completedToday = Task::where('company_id', $companyId)
            ->whereHas('statusType', function ($query) {
                $query->where('identifier', 'completed');
            })
            ->whereDate('ended_at', Carbon::today())
            ->count();
    }

    public function refreshStats()
    {
        $this->loadStats();
        $this->dispatch('stats-refreshed');
    }

    public function render()
    {
        return view('livewire.pages.dashboard')->layout('layouts.app');
    }
}
