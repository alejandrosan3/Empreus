<?php

/*
|--------------------------------------------------------------------------
| Settings area
|--------------------------------------------------------------------------
|
| Tabs shown on the left of every Settings page, in order. A tab only shows
| when the person passes its ability (see AppServiceProvider::registerPermissions).
| "null" means everyone.
|
*/

return [
    'tabs' => [
        ['route' => 'settings.preferences', 'label' => 'My preferences', 'icon' => 'user', 'ability' => null],
        ['route' => 'settings.organization', 'label' => 'Organization', 'icon' => 'building-office', 'ability' => 'view-settings'],
        ['route' => 'settings.structure', 'label' => 'Businesses & divisions', 'icon' => 'squares-2x2', 'ability' => 'view-settings'],
        ['route' => 'settings.people', 'label' => 'People', 'icon' => 'users', 'ability' => 'view-people'],
        ['route' => 'settings.roles', 'label' => 'Roles', 'icon' => 'shield-check', 'ability' => 'view-people'],
        ['route' => 'settings.task-types', 'label' => 'Task types & prices', 'icon' => 'clipboard-document-list', 'ability' => 'view-settings'],
        ['route' => 'settings.statuses', 'label' => 'Statuses', 'icon' => 'flag', 'ability' => 'view-settings'],
        ['route' => 'settings.tags', 'label' => 'Tags', 'icon' => 'tag', 'ability' => 'view-settings'],
        ['route' => 'platform.organizations', 'label' => 'All organizations', 'icon' => 'globe-alt', 'ability' => 'platform'],
    ],
];
