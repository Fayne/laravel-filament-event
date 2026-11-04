<?php

use App\Models\User;
use Fayne\Event\Enums\EventStockType;
use Fayne\Event\Enums\OrderStatus;
use Fayne\Event\Exceptions\InsufficientStockException;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventTimeslot;
use Fayne\Event\Models\Order;
use Fayne\Event\Services\ReservationService;
use Fayne\StoreLocator\Models\Store;
use Tests\Support\PackageDatabase;
use Fayne\CMS\Models\Admin;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    config(['logging.default' => 'single']);
    PackageDatabase::prepare();
});

it('deducts event stock and restores it only once when cancelled repeatedly', function () {
    $event = Event::factory()->create(['stock_type' => EventStockType::Event, 'stock' => 5]);
    $user = User::factory()->create();
    $service = app(ReservationService::class);

    $order = $service->reserve($event, $user, ['quantity' => 2]);
    expect($event->fresh()->stock)->toBe(3);

    $service->cancel($order, $user);
    $service->cancel($order, $user);
    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($event->fresh()->stock)->toBe(5);
});

it('rejects insufficient stock without creating an order', function () {
    $event = Event::factory()->create(['stock_type' => EventStockType::Event, 'stock' => 1]);
    $user = User::factory()->create();

    expect(fn () => app(ReservationService::class)->reserve($event, $user, ['quantity' => 2]))
        ->toThrow(InsufficientStockException::class);
    expect(Order::query()->count())->toBe(0)->and($event->fresh()->stock)->toBe(1);
});

it('deducts and restores store and timeslot stock through the reservation service', function () {
    $store = Store::query()->create(['name' => 'Reservation Store']);
    $storeEvent = Event::factory()->create(['stock_type' => EventStockType::Store, 'stock' => null]);
    $storeEvent->stores()->sync([$store->id => ['stock' => 3]]);
    $slotEvent = Event::factory()->create(['stock_type' => EventStockType::Timeslot, 'stock' => null]);
    $slot = EventTimeslot::query()->create([
        'event_id' => $slotEvent->id,
        'store_id' => $store->id,
        'date' => now()->addDays(5)->toDateString(),
        'start_time' => '10:00',
        'end_time' => '11:00',
        'stock' => 2,
    ]);
    $user = User::factory()->create();
    $service = app(ReservationService::class);

    $storeOrder = $service->reserve($storeEvent, $user, ['quantity' => 1, 'store_id' => $store->id]);
    expect($storeEvent->stores()->first()->pivot->stock)->toBe(2);
    $service->cancel($storeOrder, $user);
    expect($storeEvent->stores()->first()->pivot->stock)->toBe(3);

    $slotOrder = $service->reserve($slotEvent, $user, ['quantity' => 1, 'timeslot_id' => $slot->id]);
    expect($slot->fresh()->stock)->toBe(1);
    $service->cancel($slotOrder, $user);
    expect($slot->fresh()->stock)->toBe(2);
});

it('rejects another user cancelling an order and prevents cancelling after check-in', function () {
    $event = Event::factory()->create(['stock_type' => EventStockType::Event, 'stock' => 3, 'need_verify' => true]);
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $service = app(ReservationService::class);
    $order = $service->reserve($event, $owner, ['quantity' => 1]);

    expect(fn () => $service->cancel($order, $other))->toThrow(DomainException::class);
    $checkedIn = $service->checkInByCode($order->check_in_code, $owner);
    expect($checkedIn->status)->toBe(OrderStatus::CheckedIn);
    expect(fn () => $service->cancel($order, null))->toThrow(DomainException::class);
});

it('rejects check-in by another customer but permits an administrator with order update permission', function () {
    $event = Event::factory()->create(['stock_type' => EventStockType::Event, 'stock' => 2, 'need_verify' => true]);
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = app(ReservationService::class)->reserve($event, $owner, ['quantity' => 1]);

    expect(fn () => app(ReservationService::class)->checkInByCode($order->check_in_code, $other))
        ->toThrow(AuthorizationException::class);
    $this->withToken($other->createToken('check-in')->plainTextToken)
        ->postJson(eventBookingApiPrefix() . '/reservations/check-in', ['check_in_code' => $order->check_in_code])
        ->assertForbidden();

    $admin = Admin::create(['name' => 'Verifier', 'email' => 'verifier@example.test', 'password' => 'password', 'is_active' => true]);
    expect(fn () => app(ReservationService::class)->checkInByCode($order->check_in_code, $admin))
        ->toThrow(AuthorizationException::class);
    $admin->givePermissionTo(Permission::create(['name' => 'tenant.updateEventOrder', 'guard_name' => 'admin']));
    expect(app(ReservationService::class)->checkInByCode($order->check_in_code, $admin)->status)->toBe(OrderStatus::CheckedIn);
});
