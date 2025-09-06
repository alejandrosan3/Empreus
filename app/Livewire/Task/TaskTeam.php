<?php

namespace App\Livewire\Task;

use App\Models\Task;
use Flux\DateRange;
use Livewire\Component;

class TaskTeam extends Component
{
    public DateRange $range;

    /** @var array<int,array{id:int,name:string,members:array}> */
    public array $teams = [];

    /**
     * tasksByTeam keys are team_id or "none", values are arrays of task DTOs
     * [
     *    'none' => [ ['id'=>..,'title'=>..,'subdivision'=>..,'lot_number'=>..,'address'=>..,'date'=>..], ... ],
     *    1 => [ ... ],
     * ]
     * @var array<string|int,array<int,array<string,mixed>>>
     */
    public array $tasksByTeam = [];
    public ?string $currentNote = null;
    public array $checkedTasks = [];

    public function showLatestNote(int $taskId): void
    {
        $this->reset('currentNote');

        // Load only the latest note for this task, efficiently
        $task = Task::query()
            ->with(['taskNotes' => fn ($q) => $q->latest()->limit(1)])
            ->find($taskId);

        if ($task && $task->taskNotes->isNotEmpty()) {
            $this->currentNote = $task->taskNotes->first()->description;
        }
    }
    public function startBulkAssign(): void
    {
        if (! empty($this->checkedTasks)) {
            $this->dispatch('open-bulk-assign-modal', ids: $this->checkedTasks);
        }
    }

    public function render()
    {
        return view('livewire.task.task-team');
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

                        // If a team was chosen, move task to that team bucket
                        if ($this->selectedTeamId) {
                            $task->team_id = (int) $this->selectedTeamId;
                            $task->save();
                        }
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

            // Ask scheduler to recompute teams/tasks grouping (so tasks move buckets)
            $this->dispatch('refreshView');
            $this->dispatch('contentUpdated');

        } catch (\Exception $e) {
            Flux::toast(
                variant: 'danger',
                text: 'Failed to assign workers: ' . $e->getMessage(),
                heading: 'Assignment Failed'
            );
        }
    }
}
