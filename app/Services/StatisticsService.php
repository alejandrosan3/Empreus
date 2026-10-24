<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds every number shown on the Statistics page.
 *
 * A task belongs to the period by its "work date": the day it was finished,
 * or, while not finished yet, the day it is planned for.
 *
 * - Money earned:  price of finished tasks.
 * - Paid to crews: crew pay of finished tasks.
 * - What we keep:  earned minus paid to crews.
 * - Still to earn: price of tasks planned in the period but not finished.
 */
class StatisticsService
{
    /** Status identifiers that mean the work is done. */
    public const DONE_STATUSES = ['completed', 'request_payment', 'payment_pending'];

    /** How many slices a pie shows before folding the rest into "Other". */
    public const PIE_SLICES = 7;

    public function __construct(
        private int $companyId,
        private CarbonInterface $start,
        private CarbonInterface $end,
        private array $builderIds = [],
        private array $subdivisions = [],
        private array $teamIds = [],
    ) {
        $this->start = Carbon::parse($start)->startOfDay();
        $this->end   = Carbon::parse($end)->endOfDay();
    }

    public function build(): array
    {
        $tasks = $this->tasks($this->start, $this->end);
        $done  = $tasks->where('is_done', true);
        $open  = $tasks->where('is_done', false);

        $totals = $this->totals($tasks);

        // Same-length period right before this one, for "vs. before" arrows.
        $days      = $this->start->diffInDays($this->end) + 1;
        $prevEnd   = $this->start->copy()->subDay()->endOfDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1)->startOfDay();
        $previous  = $this->totals($this->tasks($prevStart, $prevEnd));

        $workers = $this->workerShares($done);

        return [
            'period' => [
                'start'      => $this->start->toDateString(),
                'end'        => $this->end->toDateString(),
                'prev_start' => $prevStart->toDateString(),
                'prev_end'   => $prevEnd->toDateString(),
            ],
            'totals'       => $totals,
            'previous'     => $previous,
            'all_time'     => $this->allTimeCounts(),
            'builders'     => $this->byBuilder($tasks),
            'subdivisions' => $this->bySubdivision($tasks),
            'zones'        => $this->byZone($tasks),
            'teams'        => $this->byTeam($done, $workers),
            'workers'      => $workers,
            'task_types'   => $this->byTaskType($done),
            'statuses'     => $this->byStatus($tasks),
            'timeline'     => $this->timeline($done),
            'map'          => $this->mapPoints($tasks),
            'has_open'     => $open->isNotEmpty(),
        ];
    }

    /**
     * One row per task in the period, already joined with what we report on.
     */
    public function tasks(CarbonInterface $start, CarbonInterface $end): Collection
    {
        $workDate = 'COALESCE(tasks.ended_at, tasks.scheduled_end_date, tasks.scheduled_start_date, tasks.created_at)';

        return DB::table('tasks')
            ->join('lots', 'lots.id', '=', 'tasks.lot_id')
            ->join('builders', 'builders.id', '=', 'lots.builder_id')
            ->leftJoin('task_types', 'task_types.id', '=', 'tasks.task_type_id')
            ->leftJoin('status_types', 'status_types.id', '=', 'tasks.status_type_id')
            ->leftJoin('teams', 'teams.id', '=', 'tasks.team_id')
            ->where('tasks.company_id', $this->companyId)
            ->where(fn ($q) => $q->whereNull('status_types.identifier')->orWhere('status_types.identifier', '!=', 'cancelled'))
            ->whereRaw("{$workDate} BETWEEN ? AND ?", [$start->toDateTimeString(), $end->toDateTimeString()])
            ->when($this->builderIds, fn ($q) => $q->whereIn('lots.builder_id', $this->builderIds))
            ->when($this->subdivisions, fn ($q) => $q->whereIn('lots.subdivision', $this->subdivisions))
            ->when($this->teamIds, fn ($q) => $q->whereIn('tasks.team_id', $this->teamIds))
            ->select([
                'tasks.id',
                'tasks.price',
                'tasks.crew_pay',
                'tasks.ended_at',
                'tasks.check_number',
                'tasks.team_id',
                'teams.name as team_name',
                'tasks.lot_id',
                'lots.subdivision',
                'lots.address',
                'lots.latitude',
                'lots.longitude',
                'lots.builder_id',
                'builders.name as builder_name',
                'task_types.name as task_type',
                'status_types.identifier as status',
                'status_types.name as status_name',
                DB::raw("{$workDate} as work_date"),
            ])
            ->get()
            ->map(function ($t) {
                $t->price       = (float) $t->price;
                $t->crew_pay    = $t->crew_pay === null ? null : (float) $t->crew_pay;
                $t->is_done     = $t->ended_at !== null || in_array($t->status, self::DONE_STATUSES, true);
                $t->is_paid     = trim((string) $t->check_number) !== '';
                $t->subdivision = trim((string) $t->subdivision) ?: 'No subdivision';
                $t->zone        = self::zoneFromAddress($t->address);
                return $t;
            });
    }

    public function totals(Collection $tasks): array
    {
        $done = $tasks->where('is_done', true);

        $earned = round($done->sum('price'), 2);
        $paid   = round($done->sum(fn ($t) => $t->crew_pay ?? 0), 2);

        return [
            'earned'            => $earned,
            'paid_to_crews'     => $paid,
            'kept'              => round($earned - $paid, 2),
            'kept_percent'      => $earned > 0 ? round(($earned - $paid) / $earned * 100, 1) : null,
            'still_to_earn'     => round($tasks->where('is_done', false)->sum('price'), 2),
            'collected'         => round($done->where('is_paid', true)->sum('price'), 2),
            'waiting_payment'   => round($done->where('is_paid', false)->sum('price'), 2),
            'tasks_done'        => $done->count(),
            'tasks_open'        => $tasks->where('is_done', false)->count(),
            'tasks_with_pay'    => $done->whereNotNull('crew_pay')->count(),
            'avg_per_task'      => $done->count() ? round($earned / $done->count(), 2) : 0,
            'lots'              => $tasks->pluck('lot_id')->unique()->count(),
            'subdivisions'      => $tasks->pluck('subdivision')->unique()->count(),
            'builders'          => $tasks->pluck('builder_id')->unique()->count(),
            'zones'             => $tasks->pluck('zone')->reject(fn ($z) => $z === 'Unknown area')->unique()->count(),
            'teams'             => $done->whereNotNull('team_id')->pluck('team_id')->unique()->count(),
        ];
    }

    /**
     * Counts that ignore the period: everything the company handles today.
     */
    private function allTimeCounts(): array
    {
        $lots = DB::table('lots')
            ->where('company_id', $this->companyId)
            ->when($this->builderIds, fn ($q) => $q->whereIn('builder_id', $this->builderIds))
            ->when($this->subdivisions, fn ($q) => $q->whereIn('subdivision', $this->subdivisions));

        return [
            'builders'     => (clone $lots)->distinct()->count('builder_id'),
            'subdivisions' => (clone $lots)->whereNotNull('subdivision')->distinct()->count('subdivision'),
            'lots'         => (clone $lots)->count(),
            'lots_located' => (clone $lots)->whereNotNull('latitude')->whereNotNull('longitude')->count(),
        ];
    }

    private function byBuilder(Collection $tasks): array
    {
        return $tasks->groupBy('builder_id')->map(function (Collection $rows) {
            $done   = $rows->where('is_done', true);
            $earned = $done->sum('price');
            $paid   = $done->sum(fn ($t) => $t->crew_pay ?? 0);

            return [
                'id'            => $rows->first()->builder_id,
                'name'          => $rows->first()->builder_name,
                'subdivisions'  => $rows->pluck('subdivision')->unique()->count(),
                'lots'          => $rows->pluck('lot_id')->unique()->count(),
                'tasks_done'    => $done->count(),
                'earned'        => round($earned, 2),
                'paid_to_crews' => round($paid, 2),
                'kept'          => round($earned - $paid, 2),
                'still_to_earn' => round($rows->where('is_done', false)->sum('price'), 2),
                'waiting_payment' => round($done->where('is_paid', false)->sum('price'), 2),
            ];
        })->sortByDesc('earned')->values()->all();
    }

    private function bySubdivision(Collection $tasks): array
    {
        $rows = $tasks->groupBy(fn ($t) => $t->builder_id . '|' . $t->subdivision)->map(function (Collection $rows) {
            $done   = $rows->where('is_done', true);
            $earned = $done->sum('price');
            $paid   = $done->sum(fn ($t) => $t->crew_pay ?? 0);

            return [
                'name'          => $rows->first()->subdivision,
                'builder_id'    => $rows->first()->builder_id,
                'builder'       => $rows->first()->builder_name,
                'zone'          => $rows->pluck('zone')->countBy()->sortDesc()->keys()->first(),
                'lots'          => $rows->pluck('lot_id')->unique()->count(),
                'tasks_done'    => $done->count(),
                'tasks_open'    => $rows->where('is_done', false)->count(),
                'earned'        => round($earned, 2),
                'paid_to_crews' => round($paid, 2),
                'kept'          => round($earned - $paid, 2),
            ];
        })->sortByDesc('earned')->values();

        // Two builders can use the same subdivision name: tell them apart.
        $nameCounts = $rows->countBy('name');

        return $rows->map(fn ($r) => $r + [
            'label' => $nameCounts[$r['name']] > 1 ? "{$r['name']} ({$r['builder']})" : $r['name'],
        ])->all();
    }

    private function byZone(Collection $tasks): array
    {
        return $tasks->groupBy('zone')->map(fn (Collection $rows, $zone) => [
            'name'         => $zone,
            'subdivisions' => $rows->pluck('subdivision')->unique()->count(),
            'lots'         => $rows->pluck('lot_id')->unique()->count(),
            'earned'       => round($rows->where('is_done', true)->sum('price'), 2),
        ])->sortByDesc('lots')->values()->all();
    }

    /**
     * Crew pay grouped by team. Tasks without a team are listed as
     * "No team" (their pay still shows per worker in the workers table).
     */
    private function byTeam(Collection $done, array $workers): array
    {
        return $done->filter(fn ($t) => $t->crew_pay !== null)
            ->groupBy(fn ($t) => $t->team_id ?? 0)
            ->map(fn (Collection $rows) => [
                'id'         => $rows->first()->team_id,
                'name'       => $rows->first()->team_name ?? 'No team',
                'tasks'      => $rows->count(),
                'paid'       => round($rows->sum('crew_pay'), 2),
                'earned'     => round($rows->sum('price'), 2),
                'avg_per_task' => round($rows->sum('crew_pay') / max($rows->count(), 1), 2),
            ])->sortByDesc('paid')->values()->all();
    }

    /**
     * Each worker's share of crew pay: a task's crew pay split evenly between
     * the workers assigned to it.
     */
    private function workerShares(Collection $done): array
    {
        $paidTasks = $done->filter(fn ($t) => ($t->crew_pay ?? 0) > 0)->keyBy('id');
        if ($paidTasks->isEmpty()) {
            return [];
        }

        $assignments = collect();
        foreach ($paidTasks->keys()->chunk(1000) as $ids) {
            $assignments = $assignments->merge(
                DB::table('task_user')
                    ->join('users', 'users.id', '=', 'task_user.user_id')
                    ->whereIn('task_user.task_id', $ids->all())
                    ->get(['task_user.task_id', 'users.id as worker_id', 'users.name'])
            );
        }

        $perTask = $assignments->groupBy('task_id')->map->count();

        return $assignments->groupBy('worker_id')->map(function (Collection $rows) use ($paidTasks, $perTask) {
            $share = $rows->sum(fn ($a) => $paidTasks[$a->task_id]->crew_pay / $perTask[$a->task_id]);

            return [
                'id'    => $rows->first()->worker_id,
                'name'  => $rows->first()->name,
                'tasks' => $rows->pluck('task_id')->unique()->count(),
                'paid'  => round($share, 2),
            ];
        })->sortByDesc('paid')->values()->all();
    }

    private function byTaskType(Collection $done): array
    {
        return $done->groupBy(fn ($t) => $t->task_type ?? 'Other')->map(fn (Collection $rows, $name) => [
            'name'   => $name,
            'tasks'  => $rows->count(),
            'earned' => round($rows->sum('price'), 2),
        ])->sortByDesc('earned')->values()->all();
    }

    private function byStatus(Collection $tasks): array
    {
        return $tasks->groupBy(fn ($t) => $t->status_name ?? 'No status')->map(fn (Collection $rows, $name) => [
            'name'  => $name,
            'tasks' => $rows->count(),
        ])->sortByDesc('tasks')->values()->all();
    }

    /**
     * Earned and paid per week (periods up to ~3 months) or per month.
     */
    private function timeline(Collection $done): array
    {
        $weekly = $this->start->diffInDays($this->end) <= 92;

        $buckets = [];
        $cursor  = $weekly ? $this->start->copy()->startOfWeek() : $this->start->copy()->startOfMonth();
        while ($cursor <= $this->end) {
            $key = $cursor->toDateString();
            $buckets[$key] = [
                'label'  => $weekly ? 'Week of ' . $cursor->format('M j') : $cursor->format('M Y'),
                'earned' => 0.0,
                'paid'   => 0.0,
            ];
            $weekly ? $cursor->addWeek() : $cursor->addMonth();
        }

        foreach ($done as $t) {
            $date = Carbon::parse($t->work_date);
            $key  = ($weekly ? $date->startOfWeek() : $date->startOfMonth())->toDateString();
            if (isset($buckets[$key])) {
                $buckets[$key]['earned'] += $t->price;
                $buckets[$key]['paid']   += $t->crew_pay ?? 0;
            }
        }

        return [
            'unit'    => $weekly ? 'week' : 'month',
            'buckets' => array_values(array_map(fn ($b) => [
                'label'  => $b['label'],
                'earned' => round($b['earned'], 2),
                'paid'   => round($b['paid'], 2),
            ], $buckets)),
        ];
    }

    /**
     * Subdivision pins (center of their located lots) and individual lot dots,
     * using only coordinates already saved on the lots.
     */
    private function mapPoints(Collection $tasks): array
    {
        $lots = $tasks->unique('lot_id');
        $located = $lots->filter(fn ($t) => $t->latitude !== null && $t->longitude !== null);

        $subdivisions = $tasks->groupBy(fn ($t) => $t->builder_id . '|' . $t->subdivision)
            ->map(function (Collection $rows) {
                $pins = $rows->unique('lot_id')->filter(fn ($t) => $t->latitude !== null && $t->longitude !== null);
                if ($pins->isEmpty()) {
                    return null;
                }

                return [
                    'name'       => $rows->first()->subdivision,
                    'builder_id' => $rows->first()->builder_id,
                    'builder'    => $rows->first()->builder_name,
                    'lat'        => round($pins->avg(fn ($t) => (float) $t->latitude), 6),
                    'lng'        => round($pins->avg(fn ($t) => (float) $t->longitude), 6),
                    'lots'       => $rows->pluck('lot_id')->unique()->count(),
                    'tasks_done' => $rows->where('is_done', true)->count(),
                    'earned'     => round($rows->where('is_done', true)->sum('price'), 2),
                ];
            })->filter()->values()->all();

        return [
            'subdivisions'  => $subdivisions,
            'lots'          => $located->take(3000)->map(fn ($t) => [
                'lat'         => (float) $t->latitude,
                'lng'         => (float) $t->longitude,
                'builder_id'  => $t->builder_id,
                'address'     => $t->address,
                'subdivision' => $t->subdivision,
            ])->values()->all(),
            'lots_total'    => $lots->count(),
            'lots_located'  => $located->count(),
        ];
    }

    /**
     * Area of a lot, read from its address: "City (ZIP)", "ZIP" or "City".
     */
    public static function zoneFromAddress(?string $address): string
    {
        $address = trim((string) $address);
        if ($address === '') {
            return 'Unknown area';
        }

        $zip = preg_match_all('/\b(\d{5})(?:-\d{4})?\b/', $address, $m) ? end($m[1]) : null;

        // "123 Main St, Houston, TX 77001" -> "Houston"
        $parts = array_values(array_filter(array_map('trim', explode(',', $address))));
        $city  = null;
        if (count($parts) >= 3) {
            $city = $parts[count($parts) - 2];
        } elseif (count($parts) === 2 && ! preg_match('/^\d/', $parts[1])) {
            $city = preg_replace('/\s+[A-Z]{2}(\s+\d{5}(-\d{4})?)?$/', '', $parts[1]);
        }
        $city = $city && ! preg_match('/\d/', $city) ? $city : null;

        return match (true) {
            $city && $zip => "{$city} ({$zip})",
            (bool) $zip   => $zip,
            (bool) $city  => $city,
            default       => 'Unknown area',
        };
    }

    /**
     * Keep the biggest slices and fold the rest into one "Other" slice.
     *
     * @return array<int, array{label: string, value: float, key: mixed}>
     */
    public static function pie(array $rows, string $valueKey, string $labelKey = 'name', string $idKey = 'name'): array
    {
        $rows = collect($rows)->filter(fn ($r) => ($r[$valueKey] ?? 0) > 0)->sortByDesc($valueKey)->values();

        $top  = $rows->take(self::PIE_SLICES);
        $rest = $rows->slice(self::PIE_SLICES);

        $slices = $top->map(fn ($r) => [
            'label' => (string) $r[$labelKey],
            'value' => round((float) $r[$valueKey], 2),
            'key'   => $r[$idKey] ?? $r[$labelKey],
        ])->all();

        if ($rest->isNotEmpty()) {
            $slices[] = [
                'label' => 'Other (' . $rest->count() . ')',
                'value' => round($rest->sum($valueKey), 2),
                'key'   => '__other',
            ];
        }

        return $slices;
    }
}
