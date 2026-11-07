<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A kind of task a business does ("Final clean", "Paint touch-up"), with a
 * default price and optional prices per builder. Archived, never deleted.
 */
class TaskType extends Model
{
    use HasFactory, BelongsToBusiness, SoftDeletes;

    protected $fillable = ['business_id', 'name', 'description', 'default_price'];

    protected $casts = [
        'default_price' => 'decimal:2',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /** Prices agreed with specific builders. */
    public function prices(): HasMany
    {
        return $this->hasMany(TaskTypePrice::class);
    }

    /** The builder's own price if there is one, otherwise the default price. */
    public function priceFor(?int $builderId): ?string
    {
        if ($builderId && ($price = $this->prices()->where('builder_id', $builderId)->value('price')) !== null) {
            return (string) $price;
        }

        return $this->default_price;
    }
}
