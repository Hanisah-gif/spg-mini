<?php

namespace Database\Factories;

use App\Models\Block;
use Illuminate\Database\Eloquent\Factories\Factory;

// STAND-IN for Person 2's shared schema. On merge, keep Person 2's version.
class BlockFactory extends Factory
{
    protected $model = Block::class;

    public function definition(): array
    {
        return [
            'name' => 'Block '.fake()->unique()->bothify('?##'),
        ];
    }
}
