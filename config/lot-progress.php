<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Columns Configuration
    |--------------------------------------------------------------------------
    |
    | Defines which columns show up in the task-progress table, their order,
    | labels, whether they’re editable/sortable/filterable, and badge colors.
    |
    */
    'columns' => [
        ['id' => 1,  'label' => 'Subdivision',        'value' => 'subdivision',         'group' => 'fixed',  'selected' => true,  'sortable' => true,  'editable' => false, 'filterable' => true,  'color' => 'amber'],
        ['id' => 2,  'label' => 'Builder',            'value' => 'builder',             'group' => 'fixed',  'selected' => true,  'sortable' => true,  'editable' => false, 'filterable' => true,  'color' => 'purple'],
        ['id' => 3,  'label' => 'Lot',                'value' => 'lot_number',          'group' => 'fixed',  'selected' => true,  'sortable' => true,  'editable' => false, 'filterable' => true,  'color' => 'cyan'],
        ['id' => 4,  'label' => 'Stories',            'value' => 'stories',             'group' => 'active', 'selected' => false, 'sortable' => false, 'editable' => true,  'filterable' => false],
        ['id' => 5,  'label' => 'Last Completed Task','value' => 'last_completed_date',  'group' => 'active', 'selected' => true,  'sortable' => true,  'editable' => false, 'filterable' => false],
        ['id' => 6,  'label' => 'Standard Tasks',     'value' => 'standard_po',         'group' => 'active', 'selected' => true,  'sortable' => false, 'editable' => false, 'filterable' => false],
        ['id' => 7,  'label' => 'EPOs',               'value' => 'epo',                 'group' => 'active', 'selected' => true,  'sortable' => false, 'editable' => false, 'filterable' => false],
        ['id' => 8,  'label' => 'Address',            'value' => 'address',             'group' => 'active', 'selected' => false, 'sortable' => true,  'editable' => true,  'filterable' => false],
        ['id' => 9,  'label' => 'Phase',              'value' => 'phase',               'group' => 'active', 'selected' => false, 'sortable' => true,  'editable' => true,  'filterable' => false, 'color' => 'indigo'],
        ['id' => 10, 'label' => 'Supervisor',         'value' => 'supervisor',          'group' => 'active', 'selected' => false, 'sortable' => true,  'editable' => true,  'filterable' => true,  'color' => 'rose'],
        ['id' => 11, 'label' => 'SQ FT',              'value' => 'floor_area',          'group' => 'active', 'selected' => false, 'sortable' => true,  'editable' => true,  'filterable' => false],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Sort Settings
    |--------------------------------------------------------------------------
    */
    'default_sort_by'        => 'id',
    'default_sort_direction' => 'desc',

    /*
    |--------------------------------------------------------------------------
    | Option Map for Inline Selects
    |--------------------------------------------------------------------------
    |
    | Maps column keys to the repository methods (or data keys) that supply
    | their available options.
    |
    */
    'option_map' => [
        'builder'     => 'builders',
        'subdivision' => 'subdivisions',
        'lot_number'  => 'lot_numbers',
        'supervisor'  => 'supervisors',
        'phase'       => 'phases',
    ],

];
