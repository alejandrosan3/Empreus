<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Subdivision extends Model
{
    use HasFactory, Searchable;

    protected $fillable = ['name', 'address'];

    public function lots()
    {
        return $this->hasMany(Lot::class);
    }

    /**
     * Data for Meilisearch indexing.
     */
    public function toSearchableArray(): array
    {
        return [
            'id'      => $this->id,
            'name'    => $this->name,
            'address' => $this->address,
        ];
    }
}
