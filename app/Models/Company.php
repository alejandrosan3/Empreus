<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A division: one branch of a business (e.g. Cleaning › Jacksonville).
 *
 * The table and class are still called "company" so existing code keeps working;
 * everywhere a person reads it, it is called a Division.
 */
class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['business_id', 'name', 'description'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_users', 'company_id', 'user_id');
    }

    public function organizationId(): ?int
    {
        return $this->business?->organization_id;
    }

    /** "Cleaning › Jacksonville" */
    public function fullName(): string
    {
        return $this->business ? $this->business->name.' › '.$this->name : $this->name;
    }
}
