<?php

namespace App\Support;

use App\Models\Builder;
use App\Models\Lot;
use App\Models\StatusType;
use App\Models\Supervisor;
use App\Models\Task;
use App\Models\TaskNote;
use App\Models\Team;
use App\Models\User;

/**
 * Which fields can be changed by double-clicking a table cell, and what a person
 * needs to change them: an area at Edit level, plus a sensitive detail for some
 * fields (prices need "money", emails need "contacts", notes need "notes").
 *
 * Anything not listed here can't be changed through the inline editor.
 */
class EditableFields
{
    /** @var array<class-string, array<string, array{0: string, 1?: string}>> */
    public const FIELDS = [
        Task::class => [
            'task_type_id' => ['tasks'],
            'status_type_id' => ['tasks'],
            'is_extra' => ['tasks'],
            'order_number' => ['tasks'],
            'scheduled_start_date' => ['tasks'],
            'scheduled_end_date' => ['tasks'],
            'started_at' => ['tasks'],
            'ended_at' => ['tasks'],
            'price' => ['tasks', 'money'],
            'invoice_number' => ['tasks', 'money'],
            'check_number' => ['tasks', 'money'],
        ],
        TaskNote::class => [
            'description' => ['tasks', 'notes'],
        ],
        Lot::class => [
            'builder_id' => ['lots'],
            'supervisor_id' => ['lots'],
            'lot_number' => ['lots'],
            'phase' => ['lots'],
            'address' => ['lots'],
            'subdivision' => ['lots'],
            'floor_area' => ['lots'],
            'num_of_floors' => ['lots'],
        ],
        Builder::class => [
            'name' => ['directory'],
            'address' => ['directory'],
            'email' => ['directory', 'contacts'],
            'phone' => ['directory', 'contacts'],
        ],
        Supervisor::class => [
            'name' => ['directory'],
            'email' => ['directory', 'contacts'],
            'phone' => ['directory', 'contacts'],
        ],
        Team::class => [
            'name' => ['crews'],
            'description' => ['crews'],
        ],
        User::class => [
            'name' => ['crews'],
        ],
    ];

    /** Columns of the progress tables that show a sensitive detail. */
    public const SENSITIVE_COLUMNS = [
        'price' => 'money',
        'invoice_number' => 'money',
        'check_number' => 'money',
        'notes' => 'notes',
        'email' => 'contacts',
        'phone' => 'contacts',
    ];

    public static function allows(?User $user, ?string $modelClass, ?string $attribute): bool
    {
        $rule = self::FIELDS[$modelClass][$attribute] ?? null;
        if (! $user || ! $rule) {
            return false;
        }

        [$area, $detail] = $rule + [1 => null];

        return $user->hasLevel($area, 'edit') && (! $detail || $user->sees($detail));
    }

    /**
     * Cancelling a task needs Full level in Tasks. Any other status change only
     * needs the Edit check the caller already does.
     */
    public static function allowsStatus(?User $user, StatusType|int|string|null $status): bool
    {
        $identifier = match (true) {
            $status instanceof StatusType => $status->identifier,
            is_int($status) || ctype_digit((string) $status) => StatusType::whereKey($status)->value('identifier'),
            default => $status,
        };

        if ($identifier === 'cancelled') {
            return (bool) $user?->hasLevel('tasks', 'full');
        }

        return true;
    }

    /** Drop table columns the person isn't allowed to see. */
    public static function visibleColumns(array $columns, ?User $user = null): array
    {
        $user ??= auth()->user();

        return array_values(array_filter($columns, function ($column) use ($user) {
            $key = is_array($column) ? ($column['value'] ?? null) : $column;
            $detail = self::SENSITIVE_COLUMNS[$key] ?? null;

            return ! $detail || ($user && $user->sees($detail));
        }));
    }
}
