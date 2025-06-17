<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use App\Models\Task;

class Projections extends Component
{
    public string $startDate;
    public string $endDate;

    public function mount()
    {
        $this->applyDefaultRange();
    }

    protected function applyDefaultRange(): void
    {
        $start = Carbon::now()->startOfWeek()->addWeek();
        $this->startDate = $start->toDateString();
        $this->endDate   = $start->copy()->endOfWeek()->toDateString();
    }

    #[Computed]
    public function getTasksProperty()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end   = Carbon::parse($this->endDate)->endOfDay();

        return Task::where('company_id', session('company_id'))
            ->whereBetween('scheduled_start_date', [$start, $end])
            ->with(['lot.builder', 'taskType'])
            ->orderBy('scheduled_start_date')
            ->get();
    }

    public function render()
    {
        return view('livewire.pages.projections')
            ->layout('layouts.app');
    }
}
