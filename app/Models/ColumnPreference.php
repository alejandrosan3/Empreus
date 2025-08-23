<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColumnPreference extends Model
{
    use HasFactory;

    protected $table = 'user_column_preferences';

    protected $fillable = [
        'user_id',
        'context',
        'columns',
    ];

    protected $casts = [
        'columns' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
