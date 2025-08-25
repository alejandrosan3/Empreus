<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function workers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_worker', 'team_id', 'worker_id')
            ->where('role', 'worker');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
