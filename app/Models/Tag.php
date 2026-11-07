<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tag extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name',
        'color',
    ];

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'tag_task')
            ->withPivot(['time_of_day'])
            ->withTimestamps();
    }
}
