<?php

namespace Fayne\Event\Enums;

enum EventStockType: int
{
    case Event = 0;
    case Store = 1;
    case Timeslot = 2;

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Event => ucfirst(self::Event->name),
            self::Store => ucfirst(self::Store->name),
            self::Timeslot => ucfirst(self::Timeslot->name),
        };
    }

    /**
     * @return string[]
     */
    public static function options(): array
    {
        return [
            self::Event->value => self::Event->label(),
            self::Store->value => self::Store->label(),
            self::Timeslot->value => self::Timeslot->label(),
        ];
    }
}
