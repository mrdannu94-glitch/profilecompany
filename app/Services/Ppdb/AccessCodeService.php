<?php

namespace App\Services\Ppdb;

use App\Models\Registration;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Pengelolaan kode akses pendaftaran.
 *
 * Kode akses hanya disimpan sebagai hash dan tidak pernah ditampilkan
 * kembali. Pemulihan kode membatalkan kode lama lalu menerbitkan kode
 * baru, tanpa pernah mengungkap nilai kode lama.
 */
class AccessCodeService
{
    public function __construct(
        protected readonly AuditLogger $audit,
    ) {}

    /**
     * Periksa apakah kode akses cocok dan masih berlaku.
     */
    public function verify(Registration $registration, string $code): bool
    {
        if ($code === '' || ! $registration->hasUsableAccessCode()) {
            return false;
        }

        if ($registration->access_code_hash === null) {
            return false;
        }

        return Hash::check($code, $registration->access_code_hash);
    }

    /**
     * Terbitkan kode akses baru dan batalkan kode lama.
     *
     * Nilai kode lama tidak pernah dikembalikan dan tidak masuk audit log.
     *
     * @return string kode akses baru untuk ditampilkan sekali
     */
    public function recover(Registration $registration, User $actor, ?string $note = null): string
    {
        $newCode = $this->generateCode();

        DB::transaction(function () use ($registration, $newCode): void {
            $registration->forceFill([
                'access_code_hash' => Hash::make($newCode),
                'access_code_expires_at' => now()->addDays((int) config('dmr.ppdb.access_code_ttl_days', 30)),
                'access_code_revoked_at' => null,
            ])->save();
        });

        $this->audit->log(
            action: 'registration.access_code.recovered',
            entityType: Registration::class,
            entityId: $registration->id,
            outcome: 'success',
            actor: $actor,
            description: 'Kode akses pendaftaran dipulihkan dan kode lama dibatalkan.',
            context: [
                'registration_number' => $registration->registration_number,
                'note' => $note,
            ],
        );

        return $newCode;
    }

    /**
     * Cabut kode akses yang berlaku, misalnya karena permintaan resmi
     * calon siswa atau kecurigaan penyalahgunaan.
     */
    public function revoke(Registration $registration, User $actor, ?string $reason = null): Registration
    {
        $registration->forceFill(['access_code_revoked_at' => now()])->save();

        $this->audit->log(
            action: 'registration.access_code.revoked',
            entityType: Registration::class,
            entityId: $registration->id,
            outcome: 'success',
            actor: $actor,
            description: 'Kode akses pendaftaran dicabut.',
            context: [
                'registration_number' => $registration->registration_number,
                'reason' => $reason,
            ],
        );

        return $registration;
    }

    /**
     * Catat percobaan akses yang gagal.
     *
     * Nomor pendaftaran dan nilai kode tidak dicatat.
     */
    public function logFailedAttempt(string $registrationNumber): void
    {
        $this->audit->log(
            action: 'registration.access_check.failed',
            entityType: Registration::class,
            entityId: null,
            outcome: 'failure',
            description: 'Percobaan pemeriksaan status memakai kredensial yang tidak valid.',
            context: ['registration_number' => $registrationNumber],
        );
    }

    /**
     * Kode akses sekali tampil.
     *
     * Alfabet menghindari karakter yang mudah tertukar (0/O, 1/I) supaya
     * calon siswa dapat menyalinnya dengan benar.
     */
    protected function generateCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;
        $code = '';

        for ($position = 0; $position < 8; $position++) {
            $code .= $alphabet[random_int(0, $max)];
        }

        return $code;
    }
}
