<?php

namespace Database\Factories;

use App\Models\Block;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaintenanceRequestFactory extends Factory
{
    protected $model = MaintenanceRequest::class;

    public function definition(): array
    {
        return [
            'resident_id' => User::factory(),
            'technician_id' => null,
            'block_id' => Block::factory(),
            'level' => fake()->numberBetween(1, 10),
            'room' => (string) fake()->numberBetween(1, 20),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(MaintenanceRequest::CATEGORIES),
            'status' => MaintenanceRequest::STATUS_PENDING,
        ];
    }
}
