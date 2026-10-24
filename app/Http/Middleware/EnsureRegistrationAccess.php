<?php

namespace App\Http\Middleware;

use App\Models\Registration;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menjaga halaman status dan unggah dokumen calon siswa.
 *
 * Calon siswa tidak memakai akun. Setelah kredensial status diverifikasi,
 * daftar pendaftaran disimpan pada sesi browser. Middleware ini memastikan
 * hanya sesi yang sudah terverifikasi yang dapat membuka halaman tersebut.
 */
class EnsureRegistrationAccess
{
    /**
     * Kunci sesi yang menyimpan pendaftaran terverifikasi.
     */
    public const SESSION_KEY = 'ppdb.registration_id';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $registrationId = $request->session()->get(self::SESSION_KEY);

        $registration = $registrationId === null
            ? null
            : Registration::query()->find($registrationId);

        if ($registration === null) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()
                ->route('ppdb.status')
                ->withErrors(['kredensial' => 'Silakan periksa status pendaftaran terlebih dahulu.']);
        }

        if (! $registration->hasUsableAccessCode()) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()
                ->route('ppdb.status')
                ->withErrors(['kredensial' => 'Kode akses tidak berlaku lagi. Hubungi CS sekolah untuk pemulihan.']);
        }

        $request->attributes->set('registration', $registration);

        return $next($request);
    }
}
