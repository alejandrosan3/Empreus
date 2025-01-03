<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'lot_id',
        'task_type_id',
        'price',
        'status_type_id',
        'scheduled_start_date',
        'scheduled_end_date',
        'started_at',
        'ended_at',
        'is_extra',
    ];

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function taskType()
    {
        return $this->belongsTo(TaskType::class);
    }

    public function taskNotes()
    {
        return $this->hasMany(TaskNote::class);
    }

    public function status()
    {
        return $this->belongsTo(StatusType::class);
    }
}
