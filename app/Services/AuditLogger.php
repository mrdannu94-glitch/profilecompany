<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Pencatat jejak aktivitas sensitif.
 *
 * Seluruh pencatatan melewati kelas ini supaya nilai yang berpotensi
 * menjadi rahasia tidak pernah tersimpan apa adanya. Kode akses, kata
 * sandi, token, dan payload pembayaran disaring sebelum ditulis.
 */
class AuditLogger
{
    /**
     * Catat satu aktivitas ke tabel `audit_logs`.
     *
     * @param  array<string, mixed>  $context
     */
    public function log(
        string $action,
        string $entityType,
        int|string|null $entityId = null,
        string $outcome = 'success',
        ?User $actor = null,
        ?string $description = null,
        array $context = [],
    ): AuditLog {
        $safeContext = AuditLog::sanitize($context);

        $audit = new AuditLog;
        $audit->forceFill([
            'actor_user_id' => $actor?->getKey(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId === null ? null : (string) $entityId,
            'outcome' => $outcome,
            'request_id' => $this->requestId(),
            'ip_address' => $this->ipAddress(),
            'description' => $this->buildDescription($description, $safeContext),
            'occurred_at' => now(),
        ])->save();

        return $audit;
    }

    /**
     * Catat percobaan yang gagal.
     *
     * Kegagalan tetap dicatat karena menunjukkan aktivitas yang perlu
     * ditinjau, tetapi pelakunya boleh kosong bila sudahlogout.
     *
     * @param  array<string, mixed>  $context
     */
    public function failure(
        string $action,
        string $entityType,
        int|string|null $entityId = null,
        ?User $actor = null,
        ?string $description = null,
        array $context = [],
    ): AuditLog {
        return $this->log($action, $entityType, $entityId, 'failure', $actor, $description, $context);
    }

    /**
     * Ringkasan yang aman untuk disimpan.
     *
     * @param  array<string, mixed>  $context
     */
    protected function buildDescription(?string $description, array $context): ?string
    {
        $parts = [];

        if ($description !== null && $description !== '') {
            $parts[] = $description;
        }

        if ($context !== []) {
            $parts[] = (string) json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($parts === []) {
            return null;
        }

        $joined = implode(' | ', $parts);

        return Str::limit($joined, 2000, '');
    }

    /**
     * ID korelasi permintaan untuk penelusuran log.
     */
    protected function requestId(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = request();

        return $request->attributes->get('request_id') ?? $request->headers->get('X-Request-Id');
    }

    /**
     * Alamat jaringan bila tersedia.
     */
    protected function ipAddress(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        return request()->ip();
    }

    /**
     * Catat kegagalan tanpa menggagalkan transaksi bisnis.
     *
     * @param  array<string, mixed>  $context
     */
    public function reportFailure(string $action, string $entityType, string $message, array $context = []): void
    {
        Log::warning('audit: '.$action, [
            'entity_type' => $entityType,
            'message' => $message,
            'context' => AuditLog::sanitize($context),
        ]);
    }
}
