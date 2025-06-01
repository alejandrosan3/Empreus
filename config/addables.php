<?php

return [
    'builder' => [
        'label'     => 'Builder',
        'icon'      => 'plus',
        'livewire'  => 'builders.create',
        'modalName' => 'add-builder',
        'route'     => 'builder-create'
    ],
    'subdivision' => [
        'label'     => 'Subdivision',
        'icon'      => 'plus',
        'livewire'  => 'subdivisions.create',
        'modalName' => 'add-subdivision',
        'route'     => 'subdivision-create'
    ],
    'supervisor' => [
        'label'     => 'Supervisor',
        'icon'      => 'plus',
        'livewire'  => 'supervisors.create',
        'modalName' => 'add-supervisor',
        'route'     => 'supervisor-create'
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
