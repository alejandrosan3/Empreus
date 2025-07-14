<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Team extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function workers()
    {
        return $this->belongsToMany(User::class, 'team_worker', 'team_id', 'worker_id')
            ->where('role', 'worker');

    }
}

