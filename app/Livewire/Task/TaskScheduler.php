<?php

namespace App\Livewire\Task;

use App\Models\StatusType;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Relaticle\Flowforge\Adapters\DefaultKanbanAdapter;
use Relaticle\Flowforge\Config\KanbanConfig;
use Relaticle\Flowforge\Contracts\KanbanBoardPageInterface;

class TaskScheduler extends Component implements KanbanBoardPageInterface
{
    public DefaultKanbanAdapter $adapter;

    public function getSubject(): Builder
    {
        return auth()->user()->tasksForCurrentCompany();
    }

    public function mount(): void
    {
        $config = (new KanbanConfig())
            ->withTitleField('order_number')
            ->withOrderField('sort_order')
            ->withColumnField('status_type_id')
            ->withColumnValues(StatusType::orderBy('id')->pluck('name', 'id')->toArray())
            ->withColumnColors(StatusType::orderBy('id')->pluck('color', 'id')->toArray())
            ->withCardAttributes([
                'taskType.name' => 'Task',
                'lot.lot_number' => 'Lot',
            ]);

        $this->adapter = new DefaultKanbanAdapter($this->getSubject(), $config);
    }

    public function render()
    {
        return view('livewire.task.task-scheduler', [
            'adapter' => $this->adapter,
        ])->layout('layouts.app');
    }
}
