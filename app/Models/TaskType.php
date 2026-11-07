<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToBusiness;

class TaskType extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = ['business_id', 'name', 'description'];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}

