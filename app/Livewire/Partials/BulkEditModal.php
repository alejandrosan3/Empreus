<?php

namespace App\Livewire\Partials;

use App\Models\Lot;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\Task;
use Illuminate\Support\Facades\Cache;

use Flux\Flux;

class BulkEditModal extends Component
{
    public array $taskIds = [];
    public $showModal = false;

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

    #[On('bulk-edit-action')]
    public function openModal($ids=[])
    {
        $this->resetModal();
        $this->showModal = true;
        $this->taskIds = $ids;
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
        ]);
    }

    public function save()
    {
        $taskFields = [];
        $lotFields = [];

        /**
         * Set Lot fields to update
         */
        if (!empty($this->supervisor_id) && is_int($this->supervisor_id)) {
            $lotFields['supervisor_id'] = $this->supervisor_id;
        }

        if (!empty($this->address)) {
            $lotFields['address'] = $this->address;
        }

        /**
         * Set task fields to update
         */

        if (!empty($this->status_type_id) && is_int($this->status_type_id)) {
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

        if (isset($this->is_extra) && is_int($this->is_extra) && in_array($this->is_extra, [0, 1])) {
            $taskFields['is_extra'] = $this->is_extra;
        }

        /**
         * Return if no fields to update
         */
        if (empty($lotFields) && empty($taskFields)) {
            $this->showModal = false;
            return;
        }

        /**
         * Bulk update tasks
         */
        if (!empty($taskFields)) {
            Task::whereIn('id', $this->taskIds)->update($taskFields);
        }

        /**
         * Bulk update lots
         */
        if (!empty($lotFields)) {
            $lotIds = Task::whereIn('id', $this->taskIds)
                ->whereNotNull('lot_id')
                ->pluck('lot_id')
                ->unique()
                ->values();

            if ($lotIds->isNotEmpty()) {
                Lot::whereIn('id', $lotIds)->update($lotFields);
            }

        }

        Flux::toast(text: 'Updated ' . count($this->taskIds) . ' tasks.', heading:'All done!', variant:'success');
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
    }
}
