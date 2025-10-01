<?php

namespace App\Livewire\Partials;

use App\Models\Lot;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\Task;
use Illuminate\Support\Facades\Cache;

use Flux\Flux;

class BulkEditModal extends Component
{
    public array $taskIds = [];
    public $showModal = false;

    public string $entity   = 'task';
    public  $recordIds = [];

    // Editable fields
    public $status_type_id;
    public $supervisor_id;
    public $address;
    public $scheduled_start_date;
    public $started_at;
    public $scheduled_end_date;
    public $ended_at;
    public $is_extra;

    // Option lists
    public array $statusTypes = [];
    public array $supervisors = [];

    // Tags
    public array $allTags = [];      // [ [id, name, color], ... ]
    public string $tagMode = 'add';  // 'add' | 'remove' | 'replace'
    public array $selectedTags = []; // [tagId, ...]
    public array $tagTimes = [];     // [tagId => 'HH:MM']


    #[On('bulk-edit-action')]
    public function openModal(string $entity='tasks', array $ids=[])
    {
        $this->resetModal();
        $this->entity     = $entity;
        $this->taskIds  = $ids;

        $this->showModal  = true;
    }

     public function resetModal()
    {
        $this->reset([
            'showModal',
            'taskIds',
            'status_type_id',
            'supervisor_id',
            'address',
            'scheduled_start_date',
            'started_at',
            'scheduled_end_date',
            'ended_at',
            'is_extra',
            'tagMode',
            'selectedTags',
            'tagTimes',
        ]);
        $this->tagMode = 'add';
    }

    public function save()
    {
        $taskFields = [];
        $lotFields = [];

        /**
         * Set Lot fields to update
         */
        if (!empty($this->supervisor_id)) {
            $lotFields['supervisor_id'] = $this->supervisor_id;
        }

        if (!empty($this->address)) {
            $lotFields['address'] = $this->address;
        }

        /**
         * Set task fields to update
         */

        if (!empty($this->status_type_id)) {
            $taskFields['status_type_id'] = $this->status_type_id;
        }

        if (!empty($this->scheduled_start_date)) {
            $taskFields['scheduled_start_date'] = $this->scheduled_start_date;
        }

        if (!empty($this->scheduled_end_date)) {
            $taskFields['scheduled_end_date'] = $this->scheduled_end_date;
        }

        if (!empty($this->started_at)) {
            $taskFields['started_at'] = $this->started_at;
        }

        if (!empty($this->ended_at)) {
            $taskFields['ended_at'] = $this->ended_at;
        }

        if (isset($this->is_extra) && in_array((int)$this->is_extra, [0, 1])) {
            $taskFields['is_extra'] = $this->is_extra;
        }

        // If nothing to update (including tags), close quietly
        $hasTagWork = !empty($this->selectedTags) && in_array($this->tagMode, ['add','remove','replace'], true);
        if (empty($lotFields) && empty($taskFields) && !$hasTagWork) {
            $this->showModal = false;
            return;
        }

        DB::transaction(function () use ($taskFields, $lotFields, $hasTagWork) {

            // Bulk update tasks
            if (!empty($taskFields) && !empty($this->taskIds)) {
                Task::whereIn('id', $this->taskIds)->update($taskFields);
            }

            // Bulk update lots
            if (!empty($lotFields) && !empty($this->taskIds)) {
                $lotIds = Task::whereIn('id', $this->taskIds)
                    ->whereNotNull('lot_id')
                    ->pluck('lot_id')
                    ->unique()
                    ->values();

                if ($lotIds->isNotEmpty()) {
                    Lot::whereIn('id', $lotIds)->update($lotFields);
                }
            }

            // Bulk TAGS
            if ($hasTagWork && !empty($this->taskIds)) {
                $tagIds = array_map('intval', $this->selectedTags);
                $tagIds = array_values(array_unique(array_filter($tagIds)));

                if (!empty($tagIds)) {
                    // Prepare times payload for add/replace
                    $syncPayload = [];
                    foreach ($tagIds as $tid) {
                        $time = $this->tagTimes[$tid] ?? null; // 'HH:MM'
                        $syncPayload[$tid] = ['time_of_day' => $time ? ($time . ':00') : null];
                    }

                    // chunk tasks to avoid huge single queries
                    foreach (array_chunk($this->taskIds, 200) as $chunk) {
                        $tasks = Task::whereIn('id', $chunk)->get(['id']);

                        foreach ($tasks as $task) {
                            if ($this->tagMode === 'add') {
                                $task->tags()->syncWithoutDetaching($syncPayload);
                            } elseif ($this->tagMode === 'remove') {
                                $task->tags()->detach($tagIds);
                            } elseif ($this->tagMode === 'replace') {
                                // Replace: wipe then set exactly the chosen tags + times
                                $task->tags()->sync($syncPayload);
                            }
                        }
                    }
                }
            }
        });

        Flux::toast(text: 'Updated ' . count($this->taskIds) . ' tasks.', heading:'All done!', variant:'success');
        $this->dispatch('resetSelected');
        $this->dispatch('contentUpdated');
        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.partials.bulk-edit-modal');
    }

     public function mount()
    {
        $companyId = session('company_id');

        $this->statusTypes = Cache::remember(
            "statusTypes.{$companyId}", 3600,
            fn() => \App\Models\StatusType::pluck('name', 'id')->toArray()
        );

        $this->supervisors = Cache::remember(
            "supervisors.{$companyId}", 3600,
            fn() => \App\Models\Supervisor::whereHas('lots', fn($q) => $q->where('company_id', $companyId))
                ->pluck('name', 'id')
                ->toArray()
        );

        $this->allTags = Cache::remember(
            "allTags", 3600,
            fn() => Tag::orderBy('name')->get(['id','name','color'])
                ->map(fn($t) => ['id'=>$t->id,'name'=>$t->name,'color'=>$t->color])
                ->toArray()
        );

    }
}
