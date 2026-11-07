<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToOrganization;
use Laravel\Scout\Searchable;

class Supervisor extends Model
{
    use HasFactory, Searchable, BelongsToOrganization;

    /**
     * @var array
     */
    protected $guarded = [];

    /** The login linked to this supervisor, if any (used for "only their own work"). */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

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
            'id'   => $this->id,
            'name' => $this->name,
        ];
    }
}
