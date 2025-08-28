<?php

namespace App\Livewire\Task;

use App\Models\Tag;
use App\Models\Task;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class EditTaskTags extends Component
{
    public int $taskId;
    public ?Task $task = null;

    // UI state
    public array $attached = []; // [ ['id'=>1, 'name'=>'Walk', 'color'=>'#...', 'time'=>'09:00'] ]
    public array $available = []; // all tags not attached
    public ?int $tagToAdd = null;

    public function mount(int $taskId): void
    {
        $this->taskId = $taskId;
        $this->loadData();
    }

    #[On('refresh-task-tags')]
    public function loadData(): void
    {
        $this->task = Task::with(['tags'])->findOrFail($this->taskId);

        $this->attached = $this->task->tags
            ->map(fn ($t) => [
                'id'    => $t->id,
                'name'  => $t->name,
                'color' => $t->color,
                'time'  => $t->pivot?->time_of_day ? substr($t->pivot->time_of_day, 0, 5) : null, // HH:MM
            ])->values()->all();

        $attachedIds = array_column($this->attached, 'id');

        $this->available = Tag::query()
            ->orderBy('name')
            ->get(['id', 'name', 'color'])
            ->reject(fn ($t) => in_array($t->id, $attachedIds, true))
            ->values()
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'color' => $t->color])
            ->all();

        // Auto-select the first available tag if none selected yet
        if ($this->tagToAdd === null && !empty($this->available)) {
            $this->tagToAdd = (int) $this->available[0]['id'];
        }
    }

    public function addSelectedTag(): void
    {
        // Normalize to int to avoid string/empty issues right after opening
        $id = (int) ($this->tagToAdd ?? 0);
        if ($id <= 0) {
            return;
        }

        $tag = Tag::find($id);
        if (!$tag) {
            return;
        }

        foreach ($this->attached as $a) {
            if ((int)$a['id'] === $tag->id) {
                return;
            }
        }

        $this->attached[] = [
            'id'    => $tag->id,
            'name'  => $tag->name,
            'color' => $tag->color,
            'time'  => null,
        ];

        $this->available = array_values(array_filter(
            $this->available,
            fn ($t) => (int)$t['id'] !== $tag->id
        ));

        // Reset selection before persisting to reflect UI state immediately
        $this->tagToAdd = null;

        $this->persist();
    }

    public function removeTag(int $tagId): void
    {
        $this->attached = array_values(array_filter($this->attached, fn ($t) => $t['id'] !== $tagId));

        $tag = Tag::find($tagId);
        if ($tag) {
            $this->available[] = ['id' => $tag->id, 'name' => $tag->name, 'color' => $tag->color];
            usort($this->available, fn ($a, $b) => strcmp($a['name'], $b['name']));
        }

        $this->persist();
    }

    public function updateTime(int $tagId, ?string $time): void
    {
        if ($time !== null && !preg_match('/^\d{2}:\d{2}$/', $time)) {
            $this->dispatch('toast', text: 'Invalid time format', variant: 'danger');
            return;
        }

        foreach ($this->attached as &$a) {
            if ($a['id'] === $tagId) {
                $a['time'] = $time ?: null;
                break;
            }
        }
        unset($a);

        $this->persist();
    }

    protected function persist(): void
    {
        $sync = [];
        foreach ($this->attached as $a) {
            $sync[$a['id']] = [
                'time_of_day' => $a['time'] ? ($a['time'] . ':00') : null,
            ];
        }

        DB::transaction(function () use ($sync) {
            $task = Task::findOrFail($this->taskId);
            $task->tags()->sync($sync);
        });

        Flux::toast(text:'Tags updated.', variant: 'success');
        $this->dispatch('refresh-task-tags'); // refresh this editor’s lists
        $this->dispatch('refreshView');       // ask parent (scheduler) to reload and update the task card
        $this->dispatch('contentUpdated');  // update task progress
    }

    public function render()
    {
        return view('livewire.task.edit-task-tags');
    }
}
