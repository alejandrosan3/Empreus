<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Task extends Model
{
    use HasFactory, Searchable;

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
        return $this->belongsToMany(User::class);
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
     * @return Collection
     */
    public static function getTasksByStatusForCurrentCompany(string $status, int $limit = 100, int $offset = 0)
    {
        $companyId = session('company_id');

        return self::query()
            ->select(
                'tasks.*',
                'lots.subdivision as subdivision',
                'lots.lot_number as lot_number',
                'lots.address as address',
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
            ->orderBy('tasks.scheduled_start_date', 'asc')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }
}
