<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Account extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function lots()
    {
        return $this->hasMany(Lot::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
