<?php

namespace Fayne\Event\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventTimeslot;

class EventTimeslotFactory extends Factory
{
    protected $model = EventTimeslot::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'store_id' => null,
            'batch_id' => null,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'stock' => 5,
        ];
    }
}
