<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder akun administrator awal.
 *
 * Kredensial diambil dari environment agar tidak ada kata sandi yang
 * dipublikasikan di dalam repository.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@dmr.test');
        $password = (string) env('ADMIN_PASSWORD', 'password');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) env('ADMIN_NAME', 'Administrator DMR'),
                'password' => Hash::make($password),
            ]
        );

        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole !== null) {
            $user->roles()->syncWithoutDetaching([$adminRole->id]);
        }
    }
}
