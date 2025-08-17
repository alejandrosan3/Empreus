<?php

namespace App\Livewire\Task;

use App\Models\Task;
use App\Traits\WithToast;
use Carbon\Carbon;
use Flux\DateRange;
use Livewire\Component;

class TaskCalendar extends Component
{
    use WithToast;

    // Coming From the Parent
    public DateRange $range;
    public $tasksByDay;

    // Local Variables
    public array $weekDays = [];
    public int   $rangeDays = 7;
    public bool   $showNoteModal = false;
    public ?string $currentNote = null;
    public array $hasMore = [];
    public array $statuses = [];
    public array $howManyMore = [];
    public array $totalTasksByDay = [];
    public array $checkedTasks = [];


    public function showLatestNote(int $taskId): void
    {
        $this->reset('currentNote');
        $task = Task::withExists(['taskNotes' => fn ($q) => $q->latest()])->find($taskId);

        if ($task?->taskNotes->isNotEmpty()) {
            $this->currentNote   = $task->taskNotes->first()->description;
        }
    }

    public function startBulkAssign(): void
    {
        if (! empty($this->checkedTasks)) {
            $this->dispatch('open-bulk-assign-modal', ids: $this->checkedTasks);
        }
    }


    public function mount()
    {
        $this->buildWeekDays();
        $this->rangeDays = count($this->weekDays);
    }

    private function buildWeekDays(): void
    {
        $this->weekDays = collect($this->range->days())
            ->map(fn (Carbon $day) => [
                'date'      => $day->toDateString(),
                'dayName'   => $day->format('D'),
                'dayNumber' => $day->format('j'),
                'isToday'   => $day->isToday(),
            ])
            ->all();
    }

    public function hydrate(): void
    {
        $this->buildWeekDays();
    }


    public function render()
    {
        return view('livewire.task.task-calendar')->layout('layouts.app', [
            'showFilters' => true,
            'currentAddable' => 'task',
        ]);
    }
}
