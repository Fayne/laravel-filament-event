<?php

namespace Fayne\Event\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Fayne\Event\Filament\Resources\BatchInsertEventSlotRecordResource;
use Fayne\Event\Filament\Resources\EventCategoryResource;
use Fayne\Event\Filament\Resources\EventResource;
use Fayne\Event\Filament\Resources\EventRuleResource;
use Fayne\Event\Filament\Resources\EventTimeslotResource;
use Fayne\Event\Filament\Resources\OrderResource;

class EventPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'event';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            EventResource::class,
            EventCategoryResource::class,
            EventTimeslotResource::class,
            OrderResource::class,
            BatchInsertEventSlotRecordResource::class,
            EventRuleResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
