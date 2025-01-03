<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lot extends Model
{
    use HasFactory;

    /**
     * @var array
     */
    protected $fillable = [
        'builder_id',
        'supervisor_id',
        'lot_number',
        'phase',
        'status_type_id',
        'address',
        'subdivision',
        'floor_area',
        'num_of_florrs',
    ];

    public function builder()
    {
        return $this->belongsTo(Builder::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function status()
    {
        return $this->belongsTo(StatusType::class);
    }
}
