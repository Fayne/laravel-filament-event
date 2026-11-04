<?php

namespace Fayne\Event\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Fayne\Event\Enums\OrderStatus;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\Order;
use Fayne\Event\Support\EventConfig;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => null,
            'timeslot_id' => null,
            'store_id' => null,
            'out_trade_no' => 'EVT-ORDER-' . Str::upper(Str::random(14)),
            'total_amount' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'payment_method' => null,
            'metadata' => null,
            'notes' => null,
            'customer_note' => null,
            'checkout_id' => null,
            'paid_at' => null,
            'status' => OrderStatus::Reserved,
            'quantity' => 1,
            'redemption_code' => null,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'gender' => null,
            'area_code' => '+86',
            'phone_number' => $this->faker->numerify('138########'),
            'check_in_code' => null,
            'check_in_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (Order $order) {
            if ($order->store_id !== null) {
                return;
            }

            $storeModel = EventConfig::getStoreModel();
            if (! method_exists($storeModel, 'query')) {
                return;
            }

            $store = $storeModel::query()->inRandomOrder()->first();
            if ($store) {
                $order->store_id = $store->id;
            }
        });
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function checkedIn(): static
    {
        return $this->state([
            'status' => OrderStatus::CheckedIn,
            'check_in_code' => 'CHK-' . Str::upper(Str::random(10)),
            'check_in_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state([
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
