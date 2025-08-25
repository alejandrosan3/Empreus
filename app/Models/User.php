<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany as EloquentHasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\ColumnPreference;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Define the relationship: A user can have many companies.
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_users', 'user_id', 'company_id');
    }

    public function lotsForCurrentCompany()
    {
        $companyId = session('company_id');

        return Lot::with(['builder', 'tasks.taskType', 'tasks.status'])
            ->where('company_id', $companyId);
    }

    public function tasksForCurrentCompany()
    {
        $companyId = session('company_id');

        return Task::query()
        ->where('tasks.company_id', $companyId);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }

    /**
     * The teams this user belongs to (for workers).
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_worker', 'worker_id', 'team_id')
            ->withTimestamps();
    }


    /**
     * The tasks assigned to this user (for workers).
     */
    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_worker', 'worker_id', 'task_id')
            ->withTimestamps();
    }

    public function columnPreferences(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ColumnPreference::class);
    }

}
