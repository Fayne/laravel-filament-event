<?php

namespace Fayne\Event\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Fayne\Event\Models\Event;
use Fayne\Event\Models\EventRule;

class EventRuleFactory extends Factory
{
    protected $model = EventRule::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => 'rule-' . Str::lower(Str::random(10)),
            'description' => $this->faker->optional()->sentence(),
            'status' => true,
            'condition' => $this->faker->randomElement(['and', 'or']),
            'conditions' => null,
            'end_other_rules' => true,
            'action_type' => 'registration',
            'weight' => 0,
        ];
    }
}
