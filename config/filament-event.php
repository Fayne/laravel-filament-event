<?php

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Fayne\CMS\Http\Middleware\OptionalSanctum;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventCategory;
use Fayne\Event\Models\EventRule;
use Fayne\Event\Models\EventTimeslot;
use Fayne\Event\Models\Order;
use Fayne\StoreLocator\Models\Store;
use App\Models\User;
use Fayne\CMS\Models\Admin;
use Fayne\Event\Models\EventCategoryTranslation;
use Fayne\Event\Models\EventTranslation;

return [
    'api' => [
        'enabled' => env('FILAMENT_EVENT_API_ENABLED', true),
        'prefix' => env('FILAMENT_EVENT_API_PREFIX', 'modules/events/api/v1'),
        'middleware' => [
            'public' => ['api'],
            'auth' => ['api', 'auth:sanctum'],
        ],
    ],
    'tables' => ['event_translations' => 'event_translations', 'event_category_translations' => 'event_category_translations'],
    'models' => [
        'event_translation' => EventTranslation::class,
        'event_category_translation' => EventCategoryTranslation::class,
        'event' => Event::class,
        'event_category' => EventCategory::class,
        'store' => Store::class,
        'event_timeslot' => EventTimeslot::class,
        'event_rule' => EventRule::class,
        'order' => Order::class,
        'user' => env('FILAMENT_EVENT_USER_MODEL', User::class),
    ],
    'stock_type' => [
        0 => Event::class,
        1 => Store::class,
        2 => EventTimeslot::class,
    ],
    'event_store_relationship' => BelongsToMany::class,
    'booking' => [
        'guest_required_fields' => ['first_name', 'last_name', 'area_code', 'phone_number'],
    ],
    'alternative_auth_middleware' => [OptionalSanctum::class . ':sanctum'],
    'admin_model' => Admin::class,
];
