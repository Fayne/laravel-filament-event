<?php

namespace Fayne\Event\Support;

use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventCategory;
use Fayne\Event\Models\EventRule;
use Fayne\Event\Models\EventTimeslot;
use Fayne\Event\Models\Order;
use Fayne\StoreLocator\Models\Store;
use App\Models\User;

class EventConfig
{
    public static function getEventModel(): string
    {
        return config('filament-event.models.event', Event::class);
    }

    public static function getEventCategoryModel(): string
    {
        return config('filament-event.models.event_category', EventCategory::class);
    }

    public static function getEventTimeslotModel(): string
    {
        return config('filament-event.models.event_timeslot', EventTimeslot::class);
    }

    public static function getEventRuleModel(): string
    {
        return config('filament-event.models.event_rule', EventRule::class);
    }

    public static function getOrderModel(): string
    {
        return config('filament-event.models.order', Order::class);
    }

    public static function getStoreModel(): string
    {
        return config('filament-event.models.store', Store::class);
    }

    public static function getUserModel(): string
    {
        return config('filament-event.models.user', User::class);
    }

    public static function getAuthApiMiddleware(): array
    {
        return (array) config('filament-event.api.middleware.auth', ['api', 'auth:sanctum']);
    }
}
