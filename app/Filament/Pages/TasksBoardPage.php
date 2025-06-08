<?php

namespace App\Filament\Pages;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\Flowforge\Filament\Pages\KanbanBoardPage;

use App\Models\StatusType;

class TasksBoardPage extends KanbanBoardPage
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';
    protected static ?string $navigationLabel = 'Task Scheduling';
    protected static ?string $title = 'Task Scheduling';

    public function getSubject(): Builder
    {
        if (auth()->check()) {
            return auth()->user()->tasksForCurrentCompany();
        }

        return Task::query();
    }

    public function mount(): void
    {
        $this
            ->titleField('order_number')
            ->orderField('sort_order')
            ->columnField('status_type_id')
            ->columns(StatusType::orderBy('id')->pluck('name', 'id')->toArray())
            ->columnColors(StatusType::orderBy('id')->pluck('color', 'id')->toArray())
            ->cardAttributes([
                'taskType.name' => 'Task',
                'lot.lot_number' => 'Lot',
            ]);
    }
}
