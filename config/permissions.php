<?php

/*
|--------------------------------------------------------------------------
| Roles and permissions
|--------------------------------------------------------------------------
|
| A role gives one level per area of the app, a few "sensitive details"
| switches, and a reach (all records, or only their own). Admins edit roles
| on Settings › Roles; this file only defines the building blocks and the
| starter roles every new organization gets.
|
*/

return [

    // Every area uses the same four levels, lowest to highest.
    'levels' => [
        'hidden' => ['value' => 0, 'label' => 'Hidden', 'description' => "Not in the menu and its pages don't open"],
        'view' => ['value' => 1, 'label' => 'View', 'description' => 'Can see it, every edit button is gone'],
        'edit' => ['value' => 2, 'label' => 'Edit', 'description' => 'Can add and change'],
        'full' => ['value' => 3, 'label' => 'Full', 'description' => 'Edit, plus archive, restore and cancel'],
    ],

    'areas' => [
        'dashboard' => ['label' => 'Dashboard', 'description' => "The home page with today's numbers"],
        'lots' => ['label' => 'Lots', 'description' => 'Lot progress, lot details, adding lots and the lot map'],
        'tasks' => ['label' => 'Tasks', 'description' => 'Task progress, board, calendar, task details, notes and tags on tasks'],
        'scheduler' => ['label' => 'Scheduler', 'description' => 'The task scheduler: moving tasks between days and assigning crews'],
        'directory' => ['label' => 'Builders & supervisors', 'description' => 'Builders, subdivisions and supervisors'],
        'crews' => ['label' => 'Workers & teams', 'description' => 'The workers list and teams'],
        'reports' => ['label' => 'Statistics & projections', 'description' => 'Statistics and projections pages'],
        'people' => ['label' => 'People & roles', 'description' => 'Inviting people, giving access and editing roles'],
        'settings' => ['label' => 'Settings', 'description' => 'Organization settings, businesses and divisions, task types, prices, statuses and tags'],
    ],

    // Switches that hide parts of a page, whatever the area level.
    'details' => [
        'money' => ['label' => 'Prices and money', 'description' => 'Task prices, invoice and check numbers, and money in statistics'],
        'contacts' => ['label' => 'Contact details', 'description' => 'Phone numbers and emails of builders, supervisors and people'],
        'notes' => ['label' => 'Notes', 'description' => 'Notes written on tasks'],
    ],

    'reaches' => [
        'all' => ['label' => 'Everything in the places they have access to'],
        'own' => ['label' => 'Only their own work', 'description' => "Tasks assigned to them or their team, and lots they supervise"],
    ],

    /*
    | Starter roles copied into every new organization. Owners always have
    | everything; the others can be renamed and changed by the organization.
    */
    'starter_roles' => [
        'owner' => [
            'name' => 'Owner',
            'description' => 'Runs the organization. Has every permission and can never be locked out.',
            'is_owner' => true,
            'levels' => '*full',
            'details' => ['money' => true, 'contacts' => true, 'notes' => true],
            'reach' => 'all',
            'assignable' => false,
        ],
        'admin' => [
            'name' => 'Admin',
            'description' => 'Office manager. Can do everything, including people and settings.',
            'levels' => '*full',
            'details' => ['money' => true, 'contacts' => true, 'notes' => true],
            'reach' => 'all',
            'assignable' => false,
        ],
        'scheduler' => [
            'name' => 'Scheduler',
            'description' => 'Dispatcher. Schedules and assigns tasks, edits lots and tasks. No prices, no settings.',
            'levels' => [
                'dashboard' => 'view', 'lots' => 'edit', 'tasks' => 'edit', 'scheduler' => 'edit',
                'directory' => 'view', 'crews' => 'view', 'reports' => 'hidden', 'people' => 'hidden', 'settings' => 'hidden',
            ],
            'details' => ['money' => false, 'contacts' => true, 'notes' => true],
            'reach' => 'all',
            'assignable' => false,
        ],
        'supervisor' => [
            'name' => 'Supervisor',
            'description' => 'Field lead. Sees and updates the tasks of their own lots and crews.',
            'levels' => [
                'dashboard' => 'view', 'lots' => 'view', 'tasks' => 'edit', 'scheduler' => 'view',
                'directory' => 'view', 'crews' => 'view', 'reports' => 'hidden', 'people' => 'hidden', 'settings' => 'hidden',
            ],
            'details' => ['money' => false, 'contacts' => true, 'notes' => true],
            'reach' => 'own',
            'assignable' => true,
        ],
        'worker' => [
            'name' => 'Worker',
            'description' => 'Crew member. Only sees tasks assigned to them or their team, marks progress and adds notes.',
            'levels' => [
                'dashboard' => 'view', 'lots' => 'hidden', 'tasks' => 'edit', 'scheduler' => 'hidden',
                'directory' => 'hidden', 'crews' => 'hidden', 'reports' => 'hidden', 'people' => 'hidden', 'settings' => 'hidden',
            ],
            'details' => ['money' => false, 'contacts' => false, 'notes' => true],
            'reach' => 'own',
            'assignable' => true,
        ],
        'viewer' => [
            'name' => 'Viewer',
            'description' => 'Accountant or builder contact. Can look at everything but change nothing.',
            'levels' => [
                'dashboard' => 'view', 'lots' => 'view', 'tasks' => 'view', 'scheduler' => 'view',
                'directory' => 'view', 'crews' => 'view', 'reports' => 'view', 'people' => 'hidden', 'settings' => 'hidden',
            ],
            'details' => ['money' => false, 'contacts' => true, 'notes' => true],
            'reach' => 'all',
            'assignable' => false,
        ],
    ],
];
