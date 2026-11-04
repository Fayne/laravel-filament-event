<?php

use App\Models\User;
use Illuminate\Support\Str;
use Fayne\Event\Enums\EventStockType;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventCategory;
use Fayne\Event\Services\ReservationService;

it('can check in by code when event need_verify is true', function () {
    $category = new EventCategory;
    $category->title = Str::uuid()->toString();
    $category->save();

    $event = new Event;
    $event->public_id = (string) Str::ulid();
    $event->event_category_id = $category->id;
    $event->internal_title = 'internal-' . Str::uuid();
    $event->title = 'Verify Event';
    $event->is_bookable = true;
    $event->status = true;
    $event->booking_auth_mode = 'authenticated';
    $event->stock_type = EventStockType::Event;
    $event->stock = 10;
    $event->need_verify = true;
    $event->save();

    $user = User::factory()->create();
    $service = app(ReservationService::class);
    $order = $service->reserve($event, $user, ['quantity' => 1]);

    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson(eventBookingApiPrefix() . '/reservations/check-in', ['check_in_code' => $order->check_in_code])
        ->assertOk()
        ->assertJsonPath('data.status', 'checked_in');
});

it('fails check in when event need_verify is false', function () {
    $category = new EventCategory;
    $category->title = Str::uuid()->toString();
    $category->save();

    $event = new Event;
    $event->public_id = (string) Str::ulid();
    $event->event_category_id = $category->id;
    $event->internal_title = 'internal-' . Str::uuid();
    $event->title = 'No Verify Event';
    $event->is_bookable = true;
    $event->status = true;
    $event->booking_auth_mode = 'authenticated';
    $event->stock_type = EventStockType::Event;
    $event->stock = 10;
    $event->need_verify = false;
    $event->save();

    $user = User::factory()->create();
    $service = app(ReservationService::class);
    $order = $service->reserve($event, $user, ['quantity' => 1]);

    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson(eventBookingApiPrefix() . '/reservations/check-in', ['check_in_code' => $order->check_in_code])
        ->assertStatus(422);
});
