<?php

use App\Models\User;
use Illuminate\Support\Str;
use Fayne\Event\Enums\EventStockType;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventCategory;
use Fayne\Event\Models\Order;

it('can reserve event with event stock', function () {
    $category = new EventCategory;
    $category->title = Str::uuid()->toString();
    $category->save();

    $event = new Event;
    $event->public_id = (string) Str::ulid();
    $event->event_category_id = $category->id;
    $event->internal_title = 'internal-' . Str::uuid();
    $event->title = 'Test Event';
    $event->is_bookable = true;
    $event->status = true;
    $event->booking_auth_mode = 'both';
    $event->stock_type = EventStockType::Event;
    $event->stock = 2;
    $event->save();

    $payload = [
        'quantity' => 1,
        'first_name' => 'A',
        'last_name' => 'B',
        'area_code' => '+86',
        'phone_number' => '13800138000',
    ];

    $this->postJson(eventBookingApiPrefix() . '/events/' . $event->public_id . '/reservations', $payload)
        ->assertCreated()
        ->assertJsonPath('data.status', 'reserved');

    $event->refresh();
    expect($event->stock)->toBe(1);

    expect(Order::query()->count())->toBe(1);
});

it('returns 409 when event stock is insufficient', function () {
    $category = new EventCategory;
    $category->title = Str::uuid()->toString();
    $category->save();

    $event = new Event;
    $event->public_id = (string) Str::ulid();
    $event->event_category_id = $category->id;
    $event->internal_title = 'internal-' . Str::uuid();
    $event->title = 'Test Event';
    $event->is_bookable = true;
    $event->status = true;
    $event->booking_auth_mode = 'both';
    $event->stock_type = EventStockType::Event;
    $event->stock = 0;
    $event->save();

    $payload = [
        'quantity' => 1,
        'first_name' => 'A',
        'last_name' => 'B',
        'area_code' => '+86',
        'phone_number' => '13800138000',
    ];

    $this->postJson(eventBookingApiPrefix() . '/events/' . $event->public_id . '/reservations', $payload)
        ->assertStatus(409);
});

it('requires auth when booking_auth_mode is authenticated', function () {
    $category = new EventCategory;
    $category->title = Str::uuid()->toString();
    $category->save();

    $event = new Event;
    $event->public_id = (string) Str::ulid();
    $event->event_category_id = $category->id;
    $event->internal_title = 'internal-' . Str::uuid();
    $event->title = 'Auth Event';
    $event->is_bookable = true;
    $event->status = true;
    $event->booking_auth_mode = 'authenticated';
    $event->stock_type = EventStockType::Event;
    $event->stock = 10;
    $event->save();

    $payload = [
        'quantity' => 1,
        'first_name' => 'A',
        'last_name' => 'B',
        'area_code' => '+86',
        'phone_number' => '13800138000',
    ];

    $this->postJson(eventBookingApiPrefix() . '/events/' . $event->public_id . '/reservations', $payload)
        ->assertUnauthorized();

    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson(eventBookingApiPrefix() . '/events/' . $event->public_id . '/reservations', ['quantity' => 1])
        ->assertStatus(422);
});
