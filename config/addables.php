<?php

return [
    'builder' => [
        'label'     => 'Builder',
        'icon'      => 'plus',
        'livewire'  => 'builders.create',
        'modalName' => 'add-builder',
        'route'     => 'builder-create',
        'ability'   => 'edit-directory', // only shown to roles with this permission
    ],
    'supervisor' => [
        'label'     => 'Supervisor',
        'icon'      => 'plus',
        'livewire'  => 'supervisors.create',
        'modalName' => 'add-supervisor',
        'route'     => 'supervisor-create',
        'ability'   => 'edit-directory', // only shown to roles with this permission
    ],
    'lot' => [
        'label'     => 'Lot',
        'icon'      => 'plus',
        'livewire'  => 'lot.add-lot-form',
        'modalName' => 'add-lot',
        'route'     => 'lot-create',
        'ability'   => 'edit-lots', // only shown to roles with this permission
    ],
    'task' => [
        'label'     => 'Task',
        'icon'      => 'plus',
        'livewire'  => 'task.add-task-form',
        'modalName' => 'add-task',
        'route'     => 'task-create',
        'ability'   => 'edit-tasks', // only shown to roles with this permission
    ],
    'worker' => [
        'label'     => 'Worker',
        'icon'      => 'plus',
        'livewire'  => 'workers.create-worker',
        'modalName' => 'add-worker',
        'route'     => 'worker-create',
        'ability'   => 'edit-crews', // only shown to roles with this permission
    ],
    'team' => [
        'label'     => 'Team',
        'icon'      => 'plus',
        'livewire'  => 'teams.create-team',
        'modalName' => 'add-team',
        'route'     => 'team-create',
        'ability'   => 'edit-crews', // only shown to roles with this permission
    ]
];
