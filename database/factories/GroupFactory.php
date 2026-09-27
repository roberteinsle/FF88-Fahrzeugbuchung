<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'receives_escalations' => false,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    public function escalation(): static
    {
        return $this->state(['receives_escalations' => true]);
    }
}
