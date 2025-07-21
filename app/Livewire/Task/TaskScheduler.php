<?php

namespace App\Livewire\Task;

use Livewire\Component;
use Flux\DateRange;
use Carbon\Carbon;

class TaskScheduler extends Component
{
    public string $activeTab = 'board';

    public DateRange $range;
    public $currentRangeStart;
    public int $rangeDays = 7;

    public function mount()
    {
        $this->range = DateRange::thisWeek();
        $this->currentRangeStart = $this->range->copy()->start();
    }

    protected function slideRange(int $direction): void
    {
        $this->currentRangeStart->addDays($direction * $this->rangeDays);

        $start = $this->currentRangeStart->copy();
        $end   = $start->copy()->addDays($this->rangeDays - 1);

        $this->range = new DateRange($start, $end);
    }

    public function previousRange()
    {
        $this->slideRange(-1);
    }

    public function nextRange()
    {
        $this->slideRange(1);
    }

    public function today()
    {
        $this->currentRangeStart = Carbon::now()->startOfWeek();
        $this->rangeDays = 7;

        $this->range = DateRange::thisWeek();
    }

    public function updatedRange()
    {
        $this->currentRangeStart = $this->range->copy()->start();
        $this->rangeDays = count($this->range->days()->toArray());
    }

    public function isCurrentRange(): bool
    {
        return $this->currentRangeStart->copy()->eq(Carbon::now()->startOfWeek());
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.task.task-scheduler')->layout('layouts.app', [
            'showFilters' => true,
            'showSearch'  => true,
            'showColumns' => true,
            'showAddNew'  => true,
            'currentAddable' => 'task',
        ]);
    }
}
