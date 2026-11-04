<?php

namespace Fayne\Event\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Fayne\Event\Enums\EventStockType;
use Fayne\Event\Enums\OrderStatus;
use Fayne\Event\Models\BatchInsertEventSlotRecord;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventCategory;
use Fayne\Event\Models\EventTimeslot;
use Fayne\Event\Models\Order;
use Fayne\Event\Support\EventConfig;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        $categories = EventCategory::query()->take(3)->get();
        if ($categories->isEmpty()) {
            $categories = EventCategory::factory()->count(3)->create();
        }

        $storeModel = EventConfig::getStoreModel();
        $stores = $storeModel::query()->take(3)->get();
        if ($stores->isEmpty() && method_exists($storeModel, 'factory')) {
            $stores = $storeModel::factory()->count(3)->create();
        }

        $eventStockEvent = Event::factory()
            ->stockTypeEvent()
            ->published()
            ->state([
                'event_category_id' => $categories->first()->id,
                'stock' => 50,
                'is_bookable' => true,
                'booking_auth_mode' => 'both',
            ])
            ->create();

        $eventStockStore = Event::factory()
            ->stockTypeStore()
            ->published()
            ->state([
                'event_category_id' => $categories->get(1)->id ?? $categories->first()->id,
                'is_bookable' => true,
                'booking_auth_mode' => 'both',
            ])
            ->create();

        if ($stores->isNotEmpty()) {
            $eventStockStore->stores()->syncWithoutDetaching([
                $stores->first()->id => ['stock' => 10],
                ($stores->get(1)->id ?? $stores->first()->id) => ['stock' => 10],
            ]);
        }

        $eventStockTimeslot = Event::factory()
            ->stockTypeTimeslot()
            ->published()
            ->state([
                'event_category_id' => $categories->get(2)->id ?? $categories->first()->id,
                'is_bookable' => true,
                'booking_auth_mode' => 'both',
            ])
            ->create();

        if ($eventStockTimeslot->stock_type === EventStockType::Timeslot) {
            $timeslotStoreId = $stores->first()->id ?? null;
            EventTimeslot::factory()
                ->count(3)
                ->state([
                    'event_id' => $eventStockTimeslot->id,
                    'store_id' => $timeslotStoreId,
                    'stock' => 5,
                ])
                ->create();
        }

        $this->seedOrdersForEvent($eventStockEvent, $stores->first()->id ?? null, null);
        $this->seedOrdersForEvent($eventStockStore, $stores->first()->id ?? null, null);

        $timeslot = $eventStockTimeslot->timeslots()->first();
        $this->seedOrdersForEvent($eventStockTimeslot, $timeslot?->store_id, $timeslot?->id);

        if (! BatchInsertEventSlotRecord::query()->exists()) {
            BatchInsertEventSlotRecord::query()->create([
                'admin_id' => null,
                'detail' => [
                    'event' => $eventStockTimeslot->internal_title,
                    'store' => $stores->first() ? [
                        'id' => $stores->first()->id,
                        'name' => $stores->first()->name ?? '',
                    ] : [],
                    'from-till' => json_encode([
                        'from' => now()->toDateString(),
                        'till' => now()->addDays(7)->toDateString(),
                    ]),
                    'week' => [1, 3, 5],
                    'start_time' => '10:00',
                    'end_time' => '11:00',
                    'stock' => 5,
                ],
            ]);
        }
    }

    protected function seedOrdersForEvent(Event $event, $storeId, $timeslotId): void
    {
        $common = [
            'event_id' => $event->id,
            'store_id' => $storeId,
            'timeslot_id' => $timeslotId,
            'quantity' => 1,
            'total_amount' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
        ];

        $this->firstOrCreateOrder(array_merge($common, [
            'out_trade_no' => $this->buildDemoOutTradeNo($event, $timeslotId, OrderStatus::Reserved->value),
            'status' => OrderStatus::Reserved->value,
        ]));

        $this->firstOrCreateOrder(array_merge($common, [
            'out_trade_no' => $this->buildDemoOutTradeNo($event, $timeslotId, OrderStatus::Cancelled->value),
            'status' => OrderStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]));

        $this->firstOrCreateOrder(array_merge($common, [
            'out_trade_no' => $this->buildDemoOutTradeNo($event, $timeslotId, OrderStatus::CheckedIn->value),
            'status' => OrderStatus::CheckedIn->value,
            'check_in_code' => 'CHK-' . Str::upper(Str::random(10)),
            'check_in_at' => now(),
        ]));
    }

    protected function buildDemoOutTradeNo(Event $event, $timeslotId, string $status): string
    {
        $timeslotPart = $timeslotId ? ('-' . substr((string) $timeslotId, 0, 8)) : '';

        return 'DEMO-EVENT-' . $event->id . $timeslotPart . '-' . $status;
    }

    protected function firstOrCreateOrder(array $attributes): Order
    {
        $attributes['out_trade_no'] = (string) $attributes['out_trade_no'];

        return Order::query()->firstOrCreate(
            [
                'out_trade_no' => $attributes['out_trade_no'],
            ],
            array_merge($attributes, [
                'first_name' => $attributes['first_name'] ?? 'Demo',
                'last_name' => $attributes['last_name'] ?? 'User',
                'area_code' => $attributes['area_code'] ?? '+86',
                'phone_number' => $attributes['phone_number'] ?? '13800138000',
            ]),
        );
    }
}
