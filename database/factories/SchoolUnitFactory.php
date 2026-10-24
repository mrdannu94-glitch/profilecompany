<?php

namespace Database\Factories;

use App\Models\SchoolUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolUnit>
 */
class SchoolUnitFactory extends Factory
{
    protected $model = SchoolUnit::class;

    public function definition(): array
    {
        $level = fake()->randomElement(['SMP', 'SMA', 'SMK']);
        $code = strtoupper($level).'-'.fake()->unique()->numerify('###');

        return [
            'code' => $code,
            'name' => $level.' DMR (placeholder)',
            'level' => $level,
            'official_status' => null,
            'address' => null,
            'phone' => null,
            'email' => null,
            'is_active' => true,
        ];
    }
}
