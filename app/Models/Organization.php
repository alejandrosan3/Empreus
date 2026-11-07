<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * The customer account that pays (e.g. "Empreus"). Holds people, roles and settings.
 */
class Organization extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'settings'];

    protected $casts = [
        'settings' => 'array',
    ];

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    /** Divisions are stored in the `companies` table. */
    public function divisions(): HasManyThrough
    {
        return $this->hasManyThrough(Company::class, Business::class);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings ?? [], $key, $default ?? config("organization.defaults.$key"));
    }
}
