<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskNote extends Model
{
    use HasFactory;

    protected $fillable = ['task_id', 'description'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
