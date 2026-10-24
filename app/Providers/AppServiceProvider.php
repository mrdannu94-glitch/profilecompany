<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerRateLimiters();
    }

    /**
     * Batas request untuk endpoint yang mudah disalahgunakan.
     *
     * Pemeriksaan status PPDB dibatasi per alamat jaringan dan per nomor
     * pendaftaran supaya percobaan tebak kode akses tidak dapat berjalan
     * tanpa jeda.
     */
    protected function registerRateLimiters(): void
    {
        RateLimiter::for('ppdb-status', function (Request $request): Limit {
            $limit = (int) config('dmr.ppdb.status_rate_limit', 10);

            return Limit::perMinute(max(1, $limit))->by(
                $request->ip().'|'.strtoupper(trim((string) $request->input('registration_number', '')))
            );
        });

        RateLimiter::for('ppdb-registration', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
