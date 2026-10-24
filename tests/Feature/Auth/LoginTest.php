<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('test');
    }

    public function test_halaman_login_tampil_untuk_pengunjung(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk Portal Internal');
    }

    public function test_pengguna_dapat_masuk_dengan_kredensial_benar(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@dmr.test',
            'password' => 'rahasia-kuat',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'staff@dmr.test',
            'password' => 'rahasia-kuat',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_gagal_tidak_mengesahkan_pengguna(): void
    {
        User::factory()->create([
            'email' => 'staff@dmr.test',
            'password' => 'rahasia-kuat',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'staff@dmr.test',
            'password' => 'kata-sandi-salah',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_pengguna_tanpa_role_diarahkan_ke_dasbor_umum(): void
    {
        User::factory()->create([
            'email' => 'tanpa-role@dmr.test',
            'password' => 'rahasia-kuat',
        ]);

        $this->post(route('login.store'), [
            'email' => 'tanpa-role@dmr.test',
            'password' => 'rahasia-kuat',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_redirect_login_ikut_role_pengguna(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@dmr.test',
            'password' => 'rahasia-kuat',
        ]);
        $admin->roles()->attach(Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
            'display_name' => 'Administrator',
        ])->id);

        $this->post(route('login.store'), [
            'email' => 'admin@dmr.test',
            'password' => 'rahasia-kuat',
        ])->assertRedirect(route('dashboard.admin'));
    }

    public function test_percobaan_login_dibatasi(): void
    {
        User::factory()->create([
            'email' => 'staff@dmr.test',
            'password' => 'rahasia-kuat',
        ]);

        $attempts = (int) config('auth.dmr.max_attempts');

        for ($i = 0; $i < $attempts; $i++) {
            $this->post(route('login.store'), [
                'email' => 'staff@dmr.test',
                'password' => 'kata-sandi-salah',
            ]);
        }

        $response = $this->post(route('login.store'), [
            'email' => 'staff@dmr.test',
            'password' => 'rahasia-kuat',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_kredensial_wajib_diisi(): void
    {
        $this->post(route('login.store'), [])
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_pengguna_tidak_bisa_membuka_halaman_login_setelah_masuk(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('login'))->assertRedirect();
    }

    public function test_pengguna_dapat_keluar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertFalse(Auth::check());
    }
}
