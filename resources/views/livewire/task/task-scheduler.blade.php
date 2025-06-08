<div class="h-[calc(100vh-12rem)]">
    @livewire('relaticle.flowforge.livewire.kanban-board', [
        'adapter' => $adapter,
        'pageClass' => \App\Livewire\Task\TaskScheduler::class,
    ])
</div>
