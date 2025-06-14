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

    public static function labels(): array
    {
        return [
            self::PENDING->value => 'Pending',
            self::CONFIRMED->value => 'Confirmed',
            self::IN_PROGRESS->value => 'In Progress',
            self::COMPLETED->value => 'Completed',
            self::BLOCKED->value => 'Blocked',
            self::PAYMENT_PENDING->value => 'Payment Pending',
            self::CANCELLED->value => 'Cancelled',
        ];
    }
}
