<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusType extends Model
{
    use HasFactory;

    protected $fillable = ['identifier', 'name', 'color'];

    public function isPending(): bool
    {
        return $this->identifier === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->identifier === 'confirmed';
    }

    public function isInProgress(): bool
    {
        return $this->identifier === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->identifier === 'completed';
    }

    public function isBlocked(): bool
    {
        return $this->identifier === 'blocked';
    }

    public function isPaymentPending(): bool
    {
        return $this->identifier === 'payment_pending';
    }

    public function isCancelled(): bool
    {
        return $this->identifier === 'cancelled';
    }
}

