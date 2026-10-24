<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Tampilkan formulir login internal.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Proses login internal tunggal untuk seluruh role.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, $this->maxAttempts())) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => __('Terlalu banyak percobaan login. Coba lagi dalam :seconds detik.', [
                    'seconds' => $seconds,
                ]),
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, $this->decaySeconds());

            throw ValidationException::withMessages([
                'email' => __('Email atau kata sandi tidak cocok dengan data kami.'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();

        return redirect()->intended($this->dashboardPathFor($request->user()));
    }

    /**
     * Tentukan tujuan dashboard sesuai role pengguna.
     */
    protected function dashboardPathFor(?User $user): string
    {
        if ($user === null) {
            return route('login');
        }

        if ($user->hasRole('admin')) {
            return route('dashboard.admin');
        }

        if ($user->hasRole('cs_admin')) {
            return route('dashboard.cs_admin');
        }

        if ($user->hasRole('ppdb_officer')) {
            return route('dashboard.ppdb_officer');
        }

        if ($user->hasRole('teacher')) {
            return route('dashboard.teacher');
        }

        if ($user->hasRole('staff')) {
            return route('dashboard.staff');
        }

        if ($user->hasRole('student')) {
            return route('dashboard.student');
        }

        return route('dashboard');
    }

    /**
     * Kunci throttle Combination email dan alamat IP.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(
            Str::lower((string) $request->input('email')).'|'.$request->ip()
        );
    }

    /**
     * Jumlah percobaan login maksimum dalam satu jendela waktu.
     */
    protected function maxAttempts(): int
    {
        return (int) config('auth.dmr.max_attempts', 5);
    }

    /**
     * Lama jendela waktu throttle dalam detik.
     */
    protected function decaySeconds(): int
    {
        return (int) config('auth.dmr.lockout_minutes', 5) * 60;
    }
}
