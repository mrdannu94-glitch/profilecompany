<?php

namespace App\Services\Ppdb;

use App\Enums\RegistrationStatus;
use App\Enums\SelectionDecisionStatus;
use App\Models\Registration;
use App\Models\SelectionDecision;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Service layer keputusan hasil seleksi.
 *
 * Hanya pengguna dengan permission `set_selection_result` yang boleh
 * memanggil service ini. Setiap keputusan baru menyimpan status sebelum
 * perubahan sehingga riwayat keputusan tetap dapat ditelusuri.
 */
class SelectionService
{
    public function __construct(
        protected readonly AuditLogger $audit,
    ) {}

    /**
     * Catat keputusan hasil seleksi untuk satu pendaftaran.
     */
    public function decide(
        Registration $registration,
        SelectionDecisionStatus $status,
        User $actor,
        ?string $note = null,
    ): SelectionDecision {
        $decision = DB::transaction(function () use ($registration, $status, $actor, $note): SelectionDecision {
            $current = $registration->currentDecision()->first();
            $previousStatus = $current?->decision_status;

            if ($current !== null) {
                $current->forceFill(['is_current' => false])->save();
            }

            $decision = new SelectionDecision;
            $decision->forceFill([
                'registration_id' => $registration->id,
                'decided_by' => $actor->getKey(),
                'decision_status' => $status,
                'decision_note' => $note,
                'decided_at' => now(),
                'is_current' => true,
                'previous_status' => $previousStatus,
            ])->save();

            if (! in_array($registration->status, [
                RegistrationStatus::Expired,
            ], true)) {
                $registration->forceFill(['status' => RegistrationStatus::Completed])->save();
            }

            return $decision;
        });

        $this->audit->log(
            action: 'ppdb.selection.decided',
            entityType: SelectionDecision::class,
            entityId: $decision->id,
            outcome: 'success',
            actor: $actor,
            description: 'Hasil seleksi ditetapkan.',
            context: [
                'registration_number' => $registration->registration_number,
                'decision_status' => $decision->decision_status->value,
                'previous_status' => $decision->previous_status?->value,
                'note' => $note,
            ],
        );

        return $decision;
    }

    /**
     * Batalkan keputusan yang berlaku sehingga pendaftaran kembali menunggu
     * keputusan baru.
     */
    public function revokeCurrent(Registration $registration, User $actor, string $reason): void
    {
        DB::transaction(function () use ($registration): void {
            $current = $registration->currentDecision()->first();

            if ($current === null) {
                return;
            }

            $current->forceFill(['is_current' => false])->save();
        });

        $this->audit->log(
            action: 'ppdb.selection.revoked',
            entityType: SelectionDecision::class,
            entityId: $registration->currentDecision()?->id,
            outcome: 'success',
            actor: $actor,
            description: 'Keputusan hasil seleksi dibatalkan.',
            context: [
                'registration_number' => $registration->registration_number,
                'reason' => $reason,
            ],
        );
    }
}
