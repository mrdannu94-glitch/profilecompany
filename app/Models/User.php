<?php

namespace App\Models;

use App\Models\Concerns\HasRoles;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Akun internal portal (Admin, CS Admin, Petugas PPDB, Guru, Staff, Siswa).
 *
 * Role dan permission disimpan di database, bukan hardcode di route. Nama
 * role selalu berasal dari server; input pengguna tidak pernah menentukan
 * kewenangannya. Kemeriksaan role dan permission tersedia melalui trait
 * {@see HasRoles}.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Notifikasi internal milik pengguna.
     *
     * Relasi ini memakai tabel `notifications` milik aplikasi, bukan tabel
     * notifikasi bawaan Laravel, sehingga kanal `database` bawaan tidak
     * dipakai pada tabel ini.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function authoredContentRevisions(): HasMany
    {
        return $this->hasMany(ContentRevision::class, 'author_id');
    }

    public function uploadedMedia(): HasMany
    {
        return $this->hasMany(Media::class, 'uploaded_by');
    }

    public function contentReviews(): HasMany
    {
        return $this->hasMany(ContentRevisionReview::class, 'reviewer_id');
    }

    /**
     * Dokumen PPDB yang diperiksa pengguna.
     */
    public function reviewedRegistrationDocuments(): HasMany
    {
        return $this->hasMany(RegistrationDocument::class, 'reviewed_by');
    }

    /**
     * Pembayaran yang diverifikasi pengguna.
     */
    public function verifiedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'verified_by');
    }

    /**
     * Keputusan seleksi yang dibuat pengguna.
     */
    public function selectionDecisions(): HasMany
    {
        return $this->hasMany(SelectionDecision::class, 'decided_by');
    }

    /**
     * Audit log aktivitas pengguna.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    /**
     * Periode PPDB yang dibuat pengguna.
     */
    public function createdPpdbPeriods(): HasMany
    {
        return $this->hasMany(PpdbPeriod::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
