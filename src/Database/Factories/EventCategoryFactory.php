<?php

namespace Fayne\Event\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Fayne\Event\Models\EventCategory;

class EventCategoryFactory extends Factory
{
    protected $model = EventCategory::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->unique()->words(3, true),
            'subtitle' => $this->faker->optional()->words(6, true),
            'description' => $this->faker->optional()->paragraph,
        ];
    }
}
