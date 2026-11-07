<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToOrganization;
use Laravel\Scout\Searchable;

class Builder extends Model
{
    use HasFactory, Searchable, BelongsToOrganization;

    /**
     * @var array
     */
    protected $guarded = [];

    public function lots(): HasMany
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
