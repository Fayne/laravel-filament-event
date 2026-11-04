<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Fayne\Event\Enums\EventStockType;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventCategory;
use Fayne\Event\Models\Order;
use Fayne\StoreLocator\Models\Store;

it('can reserve and cancel event with store stock', function () {
    $category = new EventCategory;
    $category->title = Str::uuid()->toString();
    $category->save();

    $store = new Store;
    $store->name = 'store-' . Str::uuid();
    $store->save();

    $event = new Event;
    $event->public_id = (string) Str::ulid();
    $event->event_category_id = $category->id;
    $event->internal_title = 'internal-' . Str::uuid();
    $event->title = 'Store Stock Event';
    $event->is_bookable = true;
    $event->status = true;
    $event->booking_auth_mode = 'both';
    $event->stock_type = EventStockType::Store;
    $event->stock = null;
    $event->save();

    $event->stores()->attach($store->id, ['stock' => 1]);

    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $payload = [
        'quantity' => 1,
        'store_id' => $store->id,
        'first_name' => 'A',
        'last_name' => 'B',
        'area_code' => '+86',
        'phone_number' => '13800138000',
    ];

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson(eventBookingApiPrefix() . '/events/' . $event->public_id . '/reservations', $payload)
        ->assertCreated();

    $pivot = DB::table('event_store')
        ->where('event_id', $event->id)
        ->where('store_id', $store->id)
        ->first();

    expect((int) $pivot->stock)->toBe(0);

    $order = Order::query()->firstOrFail();

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson(eventBookingApiPrefix() . '/me/reservations/' . $order->id . '/cancel')
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    $pivot = DB::table('event_store')
        ->where('event_id', $event->id)
        ->where('store_id', $store->id)
        ->first();

    expect((int) $pivot->stock)->toBe(1);
});
