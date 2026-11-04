<?php

namespace Fayne\Event\Enums;

enum OrderStatus: string
{
    case Reserved = 'reserved';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case CheckedIn = 'checked_in';

    /**
     * @return array
     */
    public static function options(): array
    {
        return array_column(self::cases(), 'value', 'name');
    }
}
