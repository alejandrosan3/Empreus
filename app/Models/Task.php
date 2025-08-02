<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder; // avoid clash with your Builder model
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
        'scheduled_start_date',
        'scheduled_end_date',
        'started_at',
        'ended_at',
        'is_extra',
        'order_number',
        'original_task_name',
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

    public function endedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('M d, Y') : null,
        );
    }

    public function startedAt(): Attribute
    {    return Attribute::make(
            get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('M d, Y') : null,    );
    }
    public function scheduledStartDate(): Attribute
    {
        return Attribute::make(get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('M d, Y') : null,);
    }
    public function scheduledEndDate(): Attribute
    {    return Attribute::make(
            get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('M d, Y') : null,    );
    }

    public function workers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user')
            ->where('role', 'worker');
    }

    /**
     * Specify the data to be indexed by Meilisearch.
     */
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
     * Get tasks by status for the current company.
     *
     * @param string $status
     * @param int $limit
     * @param int $offset
     * @param \Carbon\CarbonInterface|null $startDate
     * @param \Carbon\CarbonInterface|null $endDate
     * @return Collection
     */
    public static function getTasksByStatusForCurrentCompany(string $status, int $limit = 100, int $offset = 0, ?\Carbon\CarbonInterface $startDate = null, ?\Carbon\CarbonInterface $endDate = null)
    {
        $companyId = session('company_id');

        return self::query()
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
            ->orderBy('tasks.scheduled_start_date', 'asc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * Return all tasks for the current company whose scheduled start date
     * lies between the given dates (inclusive).
     *
     * @param \Carbon\CarbonInterface $startDate  range start (yyyy-mm-dd …)
     * @param \Carbon\CarbonInterface $endDate    range end   (yyyy-mm-dd …)
     * @param int                     $limit      max rows to return
     * @param int                     $offset     offset for pagination
     *
     * @return \Illuminate\Support\Collection     collection of Task models
     */
    public static function getTasksByDatesForCurrentCompany( \Carbon\CarbonInterface $startDate,  \Carbon\CarbonInterface $endDate,  int $limit  = 100,  int $offset = 0,
    ) {
        $companyId = session('company_id');

        return self::query()
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
            ->orderBy('tasks.scheduled_start_date', 'asc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

}
