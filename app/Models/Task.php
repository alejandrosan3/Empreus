<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Traits\Duplicatable;
use Flux\DateRange;

class Task extends Model
{
    use HasFactory, Searchable, Duplicatable;

    protected $fillable = [
        'lot_id',
        'company_id',
        'task_type_id',
        'price',
        'status_type_id',
        'team_id',
        'scheduled_start_date',
        'scheduled_end_date',
        'started_at',
        'ended_at',
        'is_extra',
        'order_number',
        'original_task_name',
        'invoice_number',
        'check_number',
    ];

    protected $casts = [
        'scheduled_start_date' => 'date',
        'scheduled_end_date'   => 'date',
        'started_at'           => 'datetime',
        'ended_at'             => 'datetime',
    ];

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function taskType(): BelongsTo
    {
        return $this->belongsTo(TaskType::class);
    }

    public function taskNotes(): HasMany
    {
        return $this->hasMany(TaskNote::class);
    }

    public function statusType(): BelongsTo
    {
        return $this->belongsTo(StatusType::class);
    }

    public function status(): BelongsTo
    {
        return $this->statusType();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function endedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('M d, Y') : null,
        );
    }

    public function startedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('M d, Y') : null,
        );
    }

    public function scheduledStartDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('M d, Y') : null,
        );
    }

    public function scheduledEndDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('M d, Y') : null,
        );
    }

    public function workers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user')
            ->where('role', 'worker');
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing(['lot.builder', 'lot.supervisor', 'taskType', 'statusType']);

        return [
            'id'          => $this->id,
            'lot_number'  => $this->lot->lot_number,
            'address'     => $this->lot->address,
            'builder'     => $this->lot->builder->name ?? null,
            'subdivision' => $this->lot->subdivision,
            'supervisor'  => $this->lot->supervisor->name ?? null,
            'task_type'   => $this->taskType->name ?? null,
            'status'      => $this->statusType->name ?? null,
        ];
    }

    public function getTaskTypeNameAttribute()
    {
        return $this->taskType->name ?? '';
    }

    public function getBuilderNameAttribute()
    {
        return $this->lot->builder->name ?? '';
    }

    public function getSupervisorNameAttribute()
    {
        return $this->lot->supervisor->name ?? '';
    }

    public function getFloorAreaCategoryAttribute(): string
    {
        $floorArea = $this->lot->floor_area;

        if ($floorArea <= 1800) {
            return 'S';
        } elseif ($floorArea <= 2250) {
            return 'M';
        } elseif ($floorArea <= 3000) {
            return 'L';
        } else {
            return 'XL';
        }
    }

    public function tags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Tag::class, 'tag_task')
            ->withPivot(['time_of_day'])
            ->withTimestamps();
    }

    public function getTagLabelsAttribute(): array
    {
        return $this->tags->map(function ($tag) {
            $time = $tag->pivot?->time_of_day;
            $formatted = $time ? \Carbon\Carbon::createFromFormat('H:i:s', $time)->format('g:i A') : null;
            return $formatted ? "{$tag->name} @ {$formatted}" : $tag->name;
        })->all();
    }

    public static function getGroupedTasksByStatusForCurrentCompany(array $statuses = [])
    {
        $companyId = session('company_id');

        // Get status types to filter (if provided)
        $statusTypes = \App\Models\StatusType::query();
        if (!empty($statuses)) {
            $statusTypes->whereIn('identifier', $statuses);
        }
        $statusTypes = $statusTypes->get();

        $grouped = [];
        foreach ($statusTypes as $statusType) {
            $grouped[$statusType->identifier] = \App\Models\Task::where('company_id', $companyId)
                ->where('status_type_id', $statusType->id)
                ->orderBy('scheduled_start_date', 'desc')
                ->get();
        }

        return $grouped;
    }

    /**
     * Retrieve tasks filtered by status for the current company.
     *
     * This method fetches tasks filtered by a specified status, associated with the
     * current company in session. It also allows filtering by scheduled start date
     * range, applying limits and offsets for pagination.
     *
     * @param string $status The identifier for the status type.
     * @param int|null $limit The maximum number of tasks to retrieve (default is 100).
     * @param int $offset The number of records to skip (default is 0).
     * @param CarbonInterface|null $startDate The start date for filtering the scheduled start date (optional).
     * @param CarbonInterface|null $endDate The end date for filtering the scheduled start date (optional).
     *
     * @return \Illuminate\Support\Collection A collection of tasks matching the criteria.
     */
    public static function getTasksByStatusForCurrentCompany(
        string           $status,
        ?int             $limit     = 100,
        int              $offset    = 0,
        ?CarbonInterface $startDate = null,
        ?CarbonInterface $endDate   = null
    ): \Illuminate\Support\Collection
    {
        $companyId = session('company_id');

        $query = self::query()
            ->select(
                'tasks.*',
                'lots.subdivision as subdivision',
                'lots.lot_number as lot_number',
                'lots.address as address',
                'lots.floor_area as floor_area',
                'task_types.name as task_type_name',
                'supervisors.name as supervisor_name',
                'builders.name as builder_name',
            )
            ->join('task_types', 'tasks.task_type_id', '=', 'task_types.id')
            ->join('lots', 'tasks.lot_id', '=', 'lots.id')
            ->join('supervisors', 'lots.supervisor_id', '=', 'supervisors.id')
            ->join('status_types', 'tasks.status_type_id', '=', 'status_types.id')
            ->join('builders', 'lots.builder_id', '=', 'builders.id')
            ->where('tasks.company_id', $companyId)
            ->where('status_types.identifier', $status)
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tasks.scheduled_start_date', [$startDate, $endDate]);
            })
            ->orderBy('tasks.scheduled_start_date', 'asc');

        // Apply offset if provided
        if ($offset > 0) {
            $query->offset($offset);
        }

        // Apply limit only if it's not null
        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Return tasks for the current company whose scheduled start date
     * lies between the given dates (inclusive)
     * @param CarbonInterface $startDate
     * @param CarbonInterface $endDate
     * @param int $limit
     * @param int $offset
     * @return Collection
     */
    public static function getTasksByDatesForCurrentCompany(CarbonInterface $startDate, CarbonInterface $endDate, int $limit  = 100, int $offset = 0): Collection
    {
        $companyId = session('company_id');

        $query = self::query()
            ->select(
                'tasks.*',
                'lots.subdivision as subdivision',
                'lots.lot_number  as lot_number',
                'lots.address     as address',
                'task_types.name  as task_type_name',
                'supervisors.name as supervisor_name',
                'builders.name    as builder_name',
            )
            ->join('task_types',  'tasks.task_type_id', '=', 'task_types.id')
            ->join('lots',        'tasks.lot_id',       '=', 'lots.id')
            ->join('supervisors', 'lots.supervisor_id', '=', 'supervisors.id')
            ->join('status_types','tasks.status_type_id','=', 'status_types.id')
            ->join('builders',    'lots.builder_id',    '=', 'builders.id')
            ->where('tasks.company_id', $companyId)
            ->whereBetween('tasks.scheduled_start_date', [$startDate, $endDate])
            ->with(['tags:id,name,color']) // Load tags relationship for Calendar view
            ->orderBy('tasks.scheduled_start_date', 'asc');

        // Apply offset if provided
        if ($offset > 0) {
            $query->offset($offset);
        }

        // Apply limit only if it's not null
        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Group tasks for a company by the team of their assigned workers.
     *
     * @param int $companyId
     * @param \Carbon\CarbonInterface $start Inclusive start of the scheduled start date range.
     * @param \Carbon\CarbonInterface $end   Inclusive end of the scheduled start date range.
     *
     * @return \Illuminate\Support\Collection<array{team:?Team,tasks:\Illuminate\Support\Collection,external:\Illuminate\Support\Collection}>
     */
    public static function groupedByTeamForCompany( int $companyId,  \Carbon\CarbonInterface $start,  \Carbon\CarbonInterface $end): \Illuminate\Support\Collection {
        $tasks = self::query()
            ->where('company_id', $companyId)
            ->whereBetween('scheduled_start_date', [$start, $end])
            ->with(['workers.teams'])
            ->get();

        $groups = collect();

        foreach ($tasks as $task) {
            $teams = $task->workers->flatMap->teams->unique('id');

            if ($teams->count() === 1) {
                $team = $teams->first();
                $key  = 'team-' . $team->id;
                $external = $task->workers->filter(
                    fn ($worker) => ! $worker->teams->contains('id', $team->id)
                );
            } else {
                $team = null;
                $key  = 'no-team';
                $external = collect();
            }

            if (! $groups->has($key)) {
                $groups[$key] = [
                    'team' => $team,
                    'tasks' => collect(),
                    'external' => collect(),
                ];
            }

            $groups[$key]['tasks']->push($task);
            if ($external->isNotEmpty()) {
                $groups[$key]['external'] = $groups[$key]['external']
                    ->merge($external)->unique('id');
            }
        }

        return $groups->values();
    }


}
