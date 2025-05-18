<?php

namespace App\Enums;

enum StatusType: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case BLOCKED = 'blocked';
    case PAYMENT_PENDING = 'payment_pending';
    case CANCELLED = 'cancelled';

    public static function all(): array
    {
        return array_map(fn(self $status) => $status->value, self::cases());
    }
}
