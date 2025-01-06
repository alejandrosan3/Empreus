<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Builder extends Model
{
    use HasFactory;

    /**
     * @var array
     */
    protected $guarded = [];

    public function lots()
    {
        return $this->hasMany(Lot::class);
    }

    // we might not need this. we'll see.
    public function account()
    {
        return $this->hasManyThrough(Account::class, Lot::class);
    }
}
