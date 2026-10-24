<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\SchoolUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'school_unit_id' => SchoolUnit::factory(),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
