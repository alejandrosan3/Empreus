<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusType extends Model
{
    use HasFactory;

    protected $fillable = ['identifier', 'name', 'color'];

    /** Colors a status badge can use. */
    public const COLORS = ['zinc', 'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose'];

    /*
     * Statuses are shared by everyone because the app's logic depends on them
     * (pending, completed…), but each business can rename them and pick their
     * colors on Settings › Statuses. These accessors apply the current business's choice.
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn ($value) => $this->businessOverride('name') ?? $value);
    }

    protected function color(): Attribute
    {
        return Attribute::get(fn ($value) => $this->businessOverride('color') ?? $value);
    }

    protected function colorDark(): Attribute
    {
        return Attribute::get(fn ($value) => $this->businessOverride('color') ?? $value);
    }

    /** The name before any business renamed it. */
    public function originalName(): string
    {
        return $this->attributes['name'] ?? '';
    }

    public function originalColor(): string
    {
        return $this->attributes['color'] ?? 'zinc';
    }

    private function businessOverride(string $key): ?string
    {
        if (! isset($this->attributes['identifier'])) {
            return null;
        }

        $value = tenancy()->business()?->setting("statuses.{$this->attributes['identifier']}.$key");

        return filled($value) ? $value : null;
    }

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

    // Changing payment_pending to request_payment, we might want to refactor this function's name later
    public function isPaymentPending(): bool
    {
        return $this->identifier === 'request_payment';
    }

    public function isCancelled(): bool
    {
        return $this->identifier === 'cancelled';
    }
}

