<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A line of work inside an organization (e.g. Cleaning, Painting, Drywall).
 * It owns its own task types, tags and wording.
 */
class Business extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['organization_id', 'name', 'settings'];

    protected $casts = [
        'settings' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** Divisions are stored in the `companies` table. */
    public function divisions(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings ?? [], $key, $default);
    }
}
