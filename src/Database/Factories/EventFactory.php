<?php

namespace Fayne\Event\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Fayne\Event\Enums\EventStockType;
use Fayne\Event\Models\Event;
use Fayne\Event\Support\EventConfig;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(4);

        return [
            'public_id' => (string) Str::ulid(),
            'event_category_id' => null,
            'promoter_type' => null,
            'promoter_id' => null,
            'internal_title' => 'EVT-' . Str::upper(Str::random(10)),
            'title' => $title,
            'slug' => Str::slug($title),
            'subtitle' => $this->faker->optional()->sentence(8),
            'description' => $this->faker->optional()->paragraph,
            'shown_in_homepage' => true,
            'is_bookable' => true,
            'is_charged' => false,
            'booking_auth_mode' => 'both',
            'stock_type' => EventStockType::Event,
            'currency' => 'CNY',
            'price' => null,
            'status' => true,
            'start_datetime' => now(),
            'end_datetime' => now()->addDays(7),
            'redemption_code' => null,
            'need_verify' => false,
            'stock' => 50,
            'weight' => 0,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Event $event) {
            if ($event->stock_type !== EventStockType::Store) {
                return;
            }

            $storeModel = EventConfig::getStoreModel();
            if (! method_exists($storeModel, 'factory')) {
                return;
            }

            $stores = $storeModel::query()->inRandomOrder()->take(2)->get();
            if ($stores->isEmpty()) {
                $stores = $storeModel::factory()->count(2)->create();
            }

            $sync = [];
            foreach ($stores as $store) {
                $sync[$store->id] = ['stock' => random_int(1, 20)];
            }

            $event->stores()->syncWithoutDetaching($sync);
        });
    }

    public function published(): static
    {
        return $this->state([
            'status' => true,
        ]);
    }

    public function draft(): static
    {
        return $this->state([
            'status' => false,
        ]);
    }

    public function stockTypeEvent(): static
    {
        return $this->state([
            'stock_type' => EventStockType::Event,
            'stock' => 50,
        ]);
    }

    public function stockTypeStore(): static
    {
        return $this->state([
            'stock_type' => EventStockType::Store,
            'stock' => null,
        ]);
    }

    public function stockTypeTimeslot(): static
    {
        return $this->state([
            'stock_type' => EventStockType::Timeslot,
            'stock' => null,
        ]);
    }

    public function authModeGuest(): static
    {
        return $this->state([
            'booking_auth_mode' => 'guest',
        ]);
    }

    public function authModeAuthenticated(): static
    {
        return $this->state([
            'booking_auth_mode' => 'authenticated',
        ]);
    }

    public function authModeBoth(): static
    {
        return $this->state([
            'booking_auth_mode' => 'both',
        ]);
    }
}
