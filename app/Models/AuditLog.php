<?php

namespace App\Models;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak aktivitas sensitif.
 *
 * Tabel ini tidak boleh menyimpan rahasia: kata sandi, hash kode akses,
 * token, atau payload pembayaran. Gunakan {@see AuditLogger}
 * yang menyaring nilai sebelum menulis.
 */
class AuditLog extends Model
{
    /**
     * Kunci yang tidak boleh ikut tersimpan di audit log.
     *
     * @var array<int, string>
     */
    public const REDACTED_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'access_code',
        'access_code_hash',
        'token',
        'api_token',
        'secret',
        'api_key',
        'authorization',
        'card_number',
        'cvv',
    ];

    protected $fillable = [
        'action',
        'entity_type',
        'entity_id',
        'outcome',
        'request_id',
        'ip_address',
        'description',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * Pelaku aktivitas. Null untuk proses sistem.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * Saring nilai yang mengandung rahasia sebelum disimpan.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function sanitize(array $values): array
    {
        $sanitized = [];

        foreach ($values as $key => $value) {
            $normalized = strtolower((string) $key);

            if (in_array($normalized, self::REDACTED_KEYS, true) || str_contains($normalized, 'password') || str_contains($normalized, 'secret')) {
                $sanitized[$key] = '[redacted]';

                continue;
            }

            $sanitized[$key] = is_array($value) ? self::sanitize($value) : $value;
        }

        return $sanitized;
    }

    /**
     * Aktivitas tertentu untuk satu entitas.
     */
    public function scopeForEntity(Builder $query, string $entityType, int|string|null $entityId = null): Builder
    {
        return $query
            ->where('entity_type', $entityType)
            ->when($entityId !== null, fn (Builder $inner) => $inner->where('entity_id', (string) $entityId));
    }
}
