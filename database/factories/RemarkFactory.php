<?php

namespace Database\Factories;

use App\Models\MaintenanceRequest;
use App\Models\Remark;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Remark>
 */
class RemarkFactory extends Factory
{
    protected $model = Remark::class;

    public function definition(): array
    {
        return [
            'maintenance_request_id' => MaintenanceRequest::factory(),
            'technician_id' => User::factory()->state(['role' => 'technician']),
            'remark' => fake()->sentence(),
        ];
    }
}
