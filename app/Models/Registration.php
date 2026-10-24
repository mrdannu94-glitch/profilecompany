<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Pendaftaran calon siswa.
 *
 * Calon siswa tidak memakai akun `users`; status diperiksa memakai
 * registration_number dan kode akses yang disimpan sebagai hash.
 *
 * Kolom yang diterbitkan server (nomor pendaftaran, kode akses, fingerprint,
 * status, waktu pengiriman) sengaja tidak ada di `$fillable` supaya tidak
 * bisa diisi dari input pengguna. Gunakan `forceFill()` pada service layer.
 */
#[Hidden(['access_code_hash', 'identity_fingerprint'])]
class Registration extends Model
{
    protected $fillable = [
        'ppdb_period_id',
        'school_unit_id',
        'program_id',
        'candidate_name',
        'date_of_birth',
        'parent_name',
        'parent_phone',
        'parent_email',
        'access_code_expires_at',
        'data_consent_at',
        'privacy_notice_version',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'access_code_expires_at' => 'datetime',
            'access_code_revoked_at' => 'datetime',
            'data_consent_at' => 'datetime',
            'submitted_at' => 'datetime',
            'status' => RegistrationStatus::class,
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PpdbPeriod::class, 'ppdb_period_id');
    }

    public function schoolUnit(): BelongsTo
    {
        return $this->belongsTo(SchoolUnit::class, 'school_unit_id');
    }

    /**
     * Program yang dipilih. Null berarti unit tidak mewajibkan program.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    /**
     * Seluruh versi dokumen yang dikirim untuk pendaftaran ini.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(RegistrationDocument::class, 'registration_id');
    }

    /**
     * Percobaan pembayaran. Satu pendaftaran dapat memiliki lebih dari satu baris.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'registration_id');
    }

    /**
     * Percobaan pembayaran yang sedang berlaku.
     */
    public function activePayment(): HasOne
    {
        return $this->hasOne(Payment::class, 'registration_id')
            ->where('is_active', true)
            ->latestOfMany();
    }

    /**
     * Riwayat keputusan seleksi.
     */
    public function selectionDecisions(): HasMany
    {
        return $this->hasMany(SelectionDecision::class, 'registration_id');
    }

    /**
     * Keputusan seleksi yang berlaku saat ini.
     */
    public function currentDecision(): HasOne
    {
        return $this->hasOne(SelectionDecision::class, 'registration_id')
            ->where('is_current', true);
    }

    /**
     * Pembatasan ke satu periode PPDB.
     */
    public function scopeForPeriod(Builder $query, int $ppdbPeriodId): Builder
    {
        return $query->where('ppdb_period_id', $ppdbPeriodId);
    }

    /**
     * Pembatasan ke salah satu status pendaftaran.
     */
    public function scopeWithStatus(Builder $query, RegistrationStatus|array $status): Builder
    {
        return $query->whereIn('status', is_array($status) ? $status : [$status]);
    }

    /**
     * Kode akses masih berlaku dan belum dicabut.
     */
    public function hasUsableAccessCode(): bool
    {
        if ($this->access_code_hash === null || $this->access_code_revoked_at !== null) {
            return false;
        }

        return $this->access_code_expires_at === null
            || $this->access_code_expires_at->isFuture();
    }
}
