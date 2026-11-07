<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * "Only their own work": for people whose role reaches only their own work,
 * tasks and lots are limited to
 *  - tasks assigned to them (directly or through one of their teams), and
 *  - tasks on lots whose supervisor record is linked to their login,
 * and the lots those tasks are on.
 *
 * Plain database subqueries are used on purpose, so these filters never
 * trigger each other.
 */
class OwnWork
{
    /** The signed-in person, if their lists should be limited; otherwise null. */
    public static function limitedUser(): ?User
    {
        if (! auth()->hasUser() || ! tenancy()->division()) {
            return null;
        }

        $user = auth()->user();

        return $user instanceof User && $user->reachIn() === 'own' ? $user : null;
    }

    /** Limit a query on the tasks table (or an alias of it) to this person's tasks. */
    public static function whereOwnTask($query, int $userId, string $table = 'tasks'): void
    {
        $query->where(function ($q) use ($userId, $table) {
            $q->whereIn("$table.id", DB::table('task_user')->select('task_id')->where('user_id', $userId))
                ->orWhereIn("$table.id", DB::table('task_worker')->select('task_id')->where('worker_id', $userId))
                ->orWhereIn("$table.team_id", DB::table('team_worker')->select('team_id')->where('worker_id', $userId))
                ->orWhereIn("$table.lot_id", self::supervisedLots($userId));
        });
    }

    /** Limit a query on the lots table to lots they supervise or have tasks on. */
    public static function whereOwnLot($query, int $userId, string $table = 'lots'): void
    {
        $query->where(function ($q) use ($userId, $table) {
            $q->whereIn("$table.id", self::supervisedLots($userId))
                ->orWhereIn("$table.id", function (QueryBuilder $sub) use ($userId) {
                    $sub->from('tasks')->select('tasks.lot_id')->whereNotNull('tasks.lot_id');
                    self::whereOwnTask($sub, $userId);
                });
        });
    }

    private static function supervisedLots(int $userId): QueryBuilder
    {
        return DB::table('lots')
            ->join('supervisors', 'supervisors.id', '=', 'lots.supervisor_id')
            ->where('supervisors.user_id', $userId)
            ->select('lots.id');
    }
}
