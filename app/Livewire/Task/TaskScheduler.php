<?php

namespace App\Livewire\Task;

use Livewire\Component;
use Flux\DateRange;
use Carbon\Carbon;
use App\Models\TaskType;
use App\Models\Team;

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

        // Preload filter options for the navigation
        $columns = [
            [
                'id' => 1,
                'label' => 'Task Type',
                'value' => 'task_type',
                'group' => 'fixed',
                'selected' => true,
                'filterable' => true,
                'filterType' => 'select',
                'color' => 'green',
            ],
            [
                'id' => 2,
                'label' => 'Team',
                'value' => 'team',
                'group' => 'active',
                'selected' => true,
                'filterable' => true,
                'filterType' => 'select',
                'color' => 'purple',
            ],
        ];

        $filters = [
            'task_type' => TaskType::orderBy('name')->get()->map(fn($t) => ['value' => $t->id, 'label' => $t->name])->toArray(),
            'team'      => Team::orderBy('name')->get()->map(fn($t) => ['value' => $t->id, 'label' => $t->name])->toArray(),
        ];

        $this->dispatch('update-columns', columns: $columns);
        $this->dispatch('filters-updated', filters: $filters);
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
        $this->dispatch('rangeChanged');
    }

    public function nextRange()
    {
        $this->slideRange(1);
        $this->dispatch('rangeChanged');
    }

    public function today()
    {
        $this->currentRangeStart = Carbon::now()->startOfWeek();
        $this->rangeDays = 7;

        $this->range = DateRange::thisWeek();
        $this->dispatch('rangeChanged');
    }

    // this is only updated when you actually handle the datepicker.
    public function updatedRange(DateRange $new)
    {
        $this->currentRangeStart = $this->range->copy()->start();
        $this->rangeDays = count($this->range->days()->toArray());
        $this->dispatch('rangeChanged');
    }

    #[Computed]
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
