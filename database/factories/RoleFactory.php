<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = fake()->unique()->slug(2);

        return [
            'name' => $name,
            'guard_name' => 'web',
            'display_name' => ucfirst(str_replace('-', ' ', $name)),
            'description' => fake()->sentence(),
        ];
    }
}
