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
        ['id' => 1,  'label' => 'Builder',           'value' => 'builder',               'group' => 'fixed',  'selected' => true,  'sortable' => true,  'editable' => false, 'filterable' => true, 'filterType' =>'select', 'color' => 'purple'],
        ['id' => 2,  'label' => 'Subdivision',       'value' => 'subdivision',           'group' => 'fixed',  'selected' => true,  'sortable' => true,  'editable' => false, 'filterable' => true, 'filterType' =>'select', 'color' => 'amber'],
        ['id' => 3,  'label' => 'Lot',               'value' => 'lot_number',            'group' => 'fixed',  'selected' => true,  'sortable' => true,  'editable' => false, 'filterable' => true, 'filterType' =>'select', 'color' => 'cyan'],
        ['id' => 4,  'label' => 'Task Type',         'value' => 'task_type',             'group' => 'active', 'selected' => true,  'sortable' => true,  'editable' => false, 'filterable' => true, 'filterType' =>'select', 'color' => 'green'],
        ['id' => 5,  'label' => 'Status',            'value' => 'status',                'group' => 'active', 'selected' => true,  'sortable' => true,  'editable' => true,  'filterable' => true, 'filterType' =>'select','color' => 'pink'],
        ['id' => 6,  'label' => 'Stories',           'value' => 'num_of_floors',         'group' => 'active', 'selected' => true,  'sortable' => true,  'editable' => true,  'filterable' => false, 'filterType' =>'input',],
        ['id' => 7,  'label' => 'Notes',             'value' => 'notes',                 'group' => 'active', 'selected' => true,  'sortable' => false, 'editable' => true,  'filterable' => false, 'filterType' =>'select',],
        ['id' => 8,  'label' => 'SQ FT',             'value' => 'floor_area',            'group' => 'active', 'selected' => true,  'sortable' => true,  'editable' => true,  'filterable' => false, 'filterType' =>'select',],
        ['id' => 9,  'label' => 'Supervisor',        'value' => 'supervisor',            'group' => 'active', 'selected' => true,  'sortable' => true,  'editable' => true,  'filterable' => true, 'filterType' =>'select', 'color' => 'rose'],
        ['id' => 10, 'label' => 'Purchase Order',    'value' => 'order_number',          'group' => 'active', 'selected' => true,  'sortable' => true,  'editable' => true,  'filterable' => false, 'filterType' =>'input',],
        ['id' => 11, 'label' => 'Planned Start',     'value' => 'scheduled_start_date',  'group' => 'active', 'selected' => true,  'sortable' => true,  'editable' => true,  'filterable' => true, 'filterType' =>'date',],
        ['id' => 12, 'label' => 'Address',           'value' => 'address',               'group' => 'active', 'selected' => false, 'sortable' => true,  'editable' => true,  'filterable' => false, 'filterType' =>'select',],
        ['id' => 13, 'label' => 'Price',             'value' => 'price',                 'group' => 'active', 'selected' => true,  'sortable' => true,  'editable' => true,  'filterable' => false, 'filterType' =>'input',],
        ['id' => 14, 'label' => 'Completion Date',   'value' => 'ended_at',              'group' => 'active', 'selected' => true,  'sortable' => true,  'editable' => true,  'filterable' => false, 'filterType' =>'date',],
        ['id' => 15, 'label' => 'Order Type',        'value' => 'is_extra',              'group' => 'active', 'selected' => true,  'sortable' => true,  'editable' => true,  'filterable' => true, 'filterType' =>'select', 'color' => 'lime'],
        ['id' => 16, 'label' => 'Start Date',        'value' => 'started_at',            'group' => 'active', 'selected' => false, 'sortable' => true,  'editable' => true,  'filterable' => false, 'filterType' =>'date',],
        ['id' => 17, 'label' => 'Planned End',       'value' => 'scheduled_end_date',    'group' => 'active', 'selected' => false, 'sortable' => true,  'editable' => true,  'filterable' => false, 'filterType' =>'date',],
        ['id' => 18, 'label' => 'Phase',             'value' => 'phase',                 'group' => 'active', 'selected' => false, 'sortable' => true,  'editable' => true,  'filterable' => false, 'filterType' =>'select', 'color' => 'indigo'],
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
        'task_type'   => 'taskTypes',
        'status'      => 'statusTypes',
        'supervisor'  => 'supervisors',
        'subdivision' => 'subdivisions',
        'is_extra'    => 'is_extra',
    ],

];
