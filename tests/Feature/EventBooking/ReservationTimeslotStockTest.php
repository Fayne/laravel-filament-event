<?php

use App\Models\User;
use Illuminate\Support\Str;
use Fayne\Event\Enums\EventStockType;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventCategory;
use Fayne\Event\Models\EventTimeslot;
use Fayne\Event\Models\Order;

it('can reserve and cancel event with timeslot stock', function () {
    $category = new EventCategory;
    $category->title = Str::uuid()->toString();
    $category->save();

    $event = new Event;
    $event->public_id = (string) Str::ulid();
    $event->event_category_id = $category->id;
    $event->internal_title = 'internal-' . Str::uuid();
    $event->title = 'Timeslot Stock Event';
    $event->is_bookable = true;
    $event->status = true;
    $event->booking_auth_mode = 'both';
    $event->stock_type = EventStockType::Timeslot;
    $event->stock = null;
    $event->save();

    $timeslot = new EventTimeslot;
    $timeslot->id = (string) Str::ulid();
    $timeslot->event_id = $event->id;
    $timeslot->store_id = null;
    $timeslot->date = now()->toDateString();
    $timeslot->start_time = '10:00';
    $timeslot->end_time = '11:00';
    $timeslot->stock = 1;
    $timeslot->save();

    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $payload = [
        'quantity' => 1,
        'timeslot_id' => $timeslot->id,
        'first_name' => 'A',
        'last_name' => 'B',
        'area_code' => '+86',
        'phone_number' => '13800138000',
    ];

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson(eventBookingApiPrefix() . '/events/' . $event->public_id . '/reservations', $payload)
        ->assertCreated();

    $timeslot->refresh();
    expect((int) $timeslot->stock)->toBe(0);

    $order = Order::query()->firstOrFail();

    $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson(eventBookingApiPrefix() . '/me/reservations/' . $order->id . '/cancel')
        ->assertOk();

    $timeslot->refresh();
    expect((int) $timeslot->stock)->toBe(1);
});
