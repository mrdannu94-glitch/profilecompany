<?php

namespace App\Services\Ppdb;

use App\Enums\PpdbPeriodStatus;
use App\Enums\RegistrationStatus;
use App\Exceptions\Ppdb\RegistrationException;
use App\Models\PpdbPeriod;
use App\Models\Program;
use App\Models\Registration;
use App\Models\SchoolUnit;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Service layer pendaftaran PPDB.
 *
 * Service ini adalah satu-satunya tempat yang menerbitkan nomor
 * pendaftaran dan kode akses. Kedua nilai tersebut tidak pernah dibaca dari
 * input pengguna dan tidak disimpan dalam bentuk yang dapat dibaca
 * kembali.
 */
class RegistrationService
{
    /**
     * Jumlah percobaan penerbitan nomor pendaftaran bila terjadi benturan
     * karena dua pendaftaran masuk bersamaan.
     */
    protected const REGISTRATION_NUMBER_ATTEMPTS = 5;

    public function __construct(
        protected readonly AuditLogger $audit,
    ) {}

    /**
     * Buat pendaftaran baru dari formulir calon siswa.
     *
     * @param  array{ppdb_period_id: int, school_unit_id: int, program_id?: int|null, candidate_name: string, date_of_birth?: string|null, parent_name?: string|null, parent_phone?: string|null, parent_email?: string|null, data_consent_at?: string|null, privacy_notice_version?: string|null}  $data
     *
     * @throws RegistrationException
     */
    public function submit(array $data): RegistrationResult
    {
        $period = PpdbPeriod::query()->findOrFail($data['ppdb_period_id']);
        $unit = SchoolUnit::query()->findOrFail($data['school_unit_id']);

        $this->assertPeriodAccepting($period);
        $this->assertUnitCovered($period, $unit);

        $program = $this->resolveProgram($unit, $data['program_id'] ?? null);

        $fingerprint = $this->fingerprint(
            (string) $data['candidate_name'],
            $data['date_of_birth'] ?? null,
            $data['parent_phone'] ?? null,
        );

        $this->assertNotDuplicate($period, $fingerprint);

        $accessCode = $this->generateAccessCode();

        $registration = $this->persist($data, $period, $unit, $program, $fingerprint, $accessCode);

        $this->audit->log(
            action: 'registration.submitted',
            entityType: Registration::class,
            entityId: $registration->id,
            outcome: 'success',
            description: 'Pendaftaran PPDB dibuat oleh calon siswa.',
            context: [
                'registration_number' => $registration->registration_number,
                'ppdb_period_id' => $period->id,
                'school_unit_id' => $unit->id,
                'program_id' => $program?->id,
            ],
        );

        return new RegistrationResult($registration, $accessCode);
    }

    /**
     * Periode harus berstatus terbuka dan berada pada rentang tanggal
     * pendaftaran.
     *
     * @throws RegistrationException
     */
    protected function assertPeriodAccepting(PpdbPeriod $period): void
    {
        if ($period->status !== PpdbPeriodStatus::Open) {
            throw RegistrationException::periodNotAccepting();
        }

        $today = now()->startOfDay();

        if ($period->registration_start !== null && $period->registration_start->startOfDay()->greaterThan($today)) {
            throw RegistrationException::periodNotAccepting();
        }

        if ($period->registration_end !== null && $period->registration_end->endOfDay()->lessThan($today)) {
            throw RegistrationException::periodNotAccepting();
        }
    }

    /**
     * Unit yang dipilih harus tercakup oleh periode.
     *
     * @throws RegistrationException
     */
    protected function assertUnitCovered(PpdbPeriod $period, SchoolUnit $unit): void
    {
        $covered = $period->schoolUnits()
            ->where('school_units.id', $unit->id)
            ->exists();

        if (! $covered) {
            throw RegistrationException::unitNotInPeriod();
        }
    }

    /**
     * Program harus milik unit yang dipilih. Bila unit memiliki program
     * aktif, calon siswa wajib memilih salah satunya.
     *
     * @throws RegistrationException
     */
    protected function resolveProgram(SchoolUnit $unit, ?int $programId): ?Program
    {
        if ($programId === null) {
            $hasActiveProgram = Program::query()
                ->where('school_unit_id', $unit->id)
                ->where('is_active', true)
                ->exists();

            if ($hasActiveProgram) {
                throw RegistrationException::programRequired();
            }

            return null;
        }

        $program = Program::query()->findOrFail($programId);

        if ($program->school_unit_id !== $unit->id || ! $program->is_active) {
            throw RegistrationException::programNotAllowed();
        }

        return $program;
    }

    /**
     * Deteksi pendaftaran ganda memakai fingerprint identitas.
     *
     * Pendaftaran lama yang sudah kedaluwarsa tidak menghalangi pendaftaran
     * baru. Fingerprint hanya dipakai sebagai sinyal, bukan bukti
     *rinkasa, sebab nama dan nomor telepon tidak unik.
     *
     * @throws RegistrationException
     */
    protected function assertNotDuplicate(PpdbPeriod $period, string $fingerprint): void
    {
        $duplicate = Registration::query()
            ->forPeriod($period->id)
            ->where('identity_fingerprint', $fingerprint)
            ->where('status', '!=', RegistrationStatus::Expired)
            ->exists();

        if ($duplicate) {
            throw RegistrationException::duplicate();
        }
    }

    /**
     * Fingerprint HMAC atas data identitas calon siswa.
     *
     * Kunci HMAC berada di konfigurasi server dan tidak disimpan di
     * tabel pendaftaran. Nilai ini tidak dapat dibalik menjadi data asli.
     */
    public function fingerprint(string $candidateName, ?string $dateOfBirth, ?string $parentPhone): string
    {
        $parts = [Str::lower(trim($candidateName))];

        if ($dateOfBirth !== null && $dateOfBirth !== '') {
            $parts[] = now()->parse($dateOfBirth)->toDateString();
        }

        if ($parentPhone !== null && $parentPhone !== '') {
            $parts[] = (string) preg_replace('/\D+/', '', $parentPhone);
        }

        return hash_hmac('sha256', implode('|', $parts), (string) config('dmr.fingerprint_key'));
    }

    /**
     * Simpan pendaftaran di dalam transaksi.
     *
     * Bentrokan nomor pendaftaran akibat dua permintaan yang datang
     * bersamaan dicoba ulang dengan nomor berikutnya.
     *
     * @param  array<string, mixed>  $data
     */
    protected function persist(
        array $data,
        PpdbPeriod $period,
        SchoolUnit $unit,
        ?Program $program,
        string $fingerprint,
        string $accessCode,
    ): Registration {
        for ($attempt = 1; $attempt <= self::REGISTRATION_NUMBER_ATTEMPTS; $attempt++) {
            try {
                return DB::transaction(function () use ($data, $period, $unit, $program, $fingerprint, $accessCode, $attempt): Registration {
                    $registration = new Registration;

                    $registration->fill([
                        'ppdb_period_id' => $period->id,
                        'school_unit_id' => $unit->id,
                        'program_id' => $program?->id,
                        'candidate_name' => $data['candidate_name'],
                        'date_of_birth' => $data['date_of_birth'] ?? null,
                        'parent_name' => $data['parent_name'] ?? null,
                        'parent_phone' => $data['parent_phone'] ?? null,
                        'parent_email' => $data['parent_email'] ?? null,
                        'data_consent_at' => $data['data_consent_at'] ?? now(),
                        'privacy_notice_version' => $data['privacy_notice_version'] ?? config('dmr.privacy_notice_version'),
                    ]);

                    $registration->forceFill([
                        'registration_number' => $this->nextRegistrationNumber($attempt),
                        'access_code_hash' => Hash::make($accessCode),
                        'access_code_expires_at' => now()->addDays((int) config('dmr.ppdb.access_code_ttl_days', 30)),
                        'identity_fingerprint' => $fingerprint,
                        'status' => RegistrationStatus::Submitted,
                        'submitted_at' => now(),
                    ]);

                    $registration->save();

                    return $registration;
                });
            } catch (QueryException $exception) {
                if (! $this->isUniqueViolation($exception) || $attempt === self::REGISTRATION_NUMBER_ATTEMPTS) {
                    throw $exception;
                }
            }
        }

        throw RegistrationException::duplicate();
    }

    /**
     * Nomor pendaftaran unik yang diterbitkan server.
     *
     * Nomor memakai format `PPDB-YYYY-0001`. Bentrokan akibat dua
     * pendaftaran bersamaan ditangani dengan mencoba nomor berikutnya.
     */
    protected function nextRegistrationNumber(int $attempt = 1): string
    {
        $prefix = (string) config('dmr.ppdb.registration_number_prefix', 'PPDB');
        $year = now()->year;
        $sequence = Registration::query()->count() + $attempt;

        return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
    }

    /**
     * Kode akses sekali tampil.
     *
     * Alfabet menghindari karakter yang mudah tertukar (0/O, 1/I) supaya
     * calon siswa dapat menyalinnya dengan benar.
     */
    protected function generateAccessCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;
        $code = '';

        for ($position = 0; $position < 8; $position++) {
            $code .= $alphabet[random_int(0, $max)];
        }

        return $code;
    }

    /**
     * Tandai pendaftaran sebagai menunggu pembayaran.
     */
    public function markAwaitingPayment(Registration $registration): Registration
    {
        if ($registration->status === RegistrationStatus::PaymentPending) {
            return $registration;
        }

        $registration->forceFill(['status' => RegistrationStatus::PaymentPending])->save();

        return $registration;
    }

    /**
     * Kedaluwarkan pendaftaran.
     */
    public function markExpired(Registration $registration): Registration
    {
        $registration->forceFill(['status' => RegistrationStatus::Expired])->save();

        return $registration;
    }

    /**
     * Tangani benturan unik saat menyimpan pendaftaran.
     */
    public function isUniqueViolation(QueryException $exception): bool
    {
        return str_contains((string) $exception->getMessage(), 'registrations.registration_number');
    }
}
