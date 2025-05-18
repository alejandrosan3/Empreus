<?php

return [
    'builder' => [
        'label'     => 'Builder',
        'icon'      => 'plus',
        'livewire'  => 'builders.create',
        'modalName' => 'add-builder',
        'route'     => 'builder-create'
    ],
    'lot' => [
        'label'     => 'Lot',
        'icon'      => 'plus',
        'livewire'  => 'lot.add-lot-form',
        'modalName' => 'add-lot',
        'route'     => 'lot-create'
    ],
    'task' => [
        'label'     => 'Task',
        'icon'      => 'plus',
        'livewire'  => 'task.add-task-form',
        'modalName' => 'add-task',
        'route'     => 'task-create'
    ]
];
