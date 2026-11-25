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
        // Realistic sample titles per category.
        $issues = [
            'Electrical' => ['Ceiling fan not working', 'Light bulb blown', 'Power socket sparking', 'Room light flickering'],
            'Plumbing' => ['Leaking bathroom tap', 'Clogged sink', 'Toilet flush not working', 'No water in shower'],
            'Furniture' => ['Broken wardrobe door', 'Bed frame cracked', 'Study chair broken', 'Drawer stuck'],
            'Cleaning' => ['Pest problem in room', 'Mould on bathroom wall', 'Rubbish not collected', 'Stain on carpet'],
            'Other' => ['Window cannot close', 'Door lock jammed', 'Wi-Fi not reachable', 'Curtain rail fell off'],
        ];

        $category = fake()->randomElement(MaintenanceRequest::CATEGORIES);

        return [
            'resident_id' => User::factory(),
            'technician_id' => null,
            'block_id' => Block::factory(),
            'level' => fake()->numberBetween(1, 10),
            'room' => (string) fake()->numberBetween(1, 20),
            'title' => fake()->randomElement($issues[$category] ?? $issues['Other']),
            'description' => 'Problem noticed on '.fake()->dayOfWeek().'. Please check as soon as possible.',
            'category' => $category,
            'status' => MaintenanceRequest::STATUS_PENDING,
        ];
    }
}
