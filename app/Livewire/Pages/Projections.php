<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Carbon\Carbon;
use Flux\DateRange;
use App\Models\{Task, StatusType};

class Projections extends Component
{
    public DateRange $range;

    public function mount()
    {
        $this->applyDefaultRange();
    }

    protected function applyDefaultRange(): void
    {
        $start = Carbon::now()->startOfWeek()->addWeek();
        $this->range = new DateRange($start, $start->copy()->endOfWeek());
    }

    #[Computed]
    public function getTasksProperty()
    {
        $confirmedId = StatusType::where('identifier', 'confirmed')->value('id');

        return Task::where('company_id', session('company_id'))
            ->where('status_type_id', $confirmedId)
            ->whereBetween('scheduled_start_date', $this->range)
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
