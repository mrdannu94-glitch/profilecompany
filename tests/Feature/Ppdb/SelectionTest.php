<?php

namespace Tests\Feature\Ppdb;

use App\Enums\RegistrationStatus;
use App\Enums\SelectionDecisionStatus;
use App\Models\SchoolUnit;
use App\Models\SelectionDecision;

/**
 * Penetapan hasil seleksi.
 */
class SelectionTest extends PpdbScenario
{
    public function test_hanya_pemegang_permission_khusus_yang_bisa_menetapkan_hasil(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit);

        $user = $this->makeUserWithPermission(['verify_ppdb_documents']);

        $this->actingAs($user)
            ->post(route('admin.ppdb.selection.store', $registration), [
                'decision_status' => SelectionDecisionStatus::Accepted->value,
            ])
            ->assertForbidden();

        $this->assertSame(0, SelectionDecision::query()->count());
    }

    public function test_pemegang_permission_bisa_menetapkan_hasil(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit);

        $authority = $this->makeUserWithPermission(['set_selection_result']);

        $this->actingAs($authority)
            ->post(route('admin.ppdb.selection.store', $registration), [
                'decision_status' => SelectionDecisionStatus::Accepted->value,
                'note' => 'Lolos seleksi.',
            ])
            ->assertSessionHasNoErrors();

        $decision = SelectionDecision::query()->firstOrFail();

        $this->assertSame(SelectionDecisionStatus::Accepted, $decision->decision_status);
        $this->assertSame($authority->id, $decision->decided_by);
        $this->assertTrue($decision->is_current);
        $this->assertSame(RegistrationStatus::Completed, $registration->fresh()->status);
    }

    public function test_keputusan_baru_menyimpan_status_sebelumnya(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit);

        $authority = $this->makeUserWithPermission(['set_selection_result']);

        $this->actingAs($authority)->post(route('admin.ppdb.selection.store', $registration), [
            'decision_status' => SelectionDecisionStatus::Waitlisted->value,
        ]);

        $this->actingAs($authority)->post(route('admin.ppdb.selection.store', $registration), [
            'decision_status' => SelectionDecisionStatus::Accepted->value,
        ]);

        $this->assertSame(2, SelectionDecision::query()->count());

        $current = $registration->fresh()->currentDecision;

        $this->assertSame(SelectionDecisionStatus::Accepted, $current->decision_status);
        $this->assertSame(SelectionDecisionStatus::Waitlisted, $current->previous_status);
        $this->assertSame(1, SelectionDecision::query()->where('is_current', true)->count());
    }

    public function test_status_hasil_tidak_dikenal_ditolak(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit);

        $authority = $this->makeUserWithPermission(['set_selection_result']);

        $this->actingAs($authority)
            ->post(route('admin.ppdb.selection.store', $registration), [
                'decision_status' => 'memblak',
            ])
            ->assertSessionHasErrors('decision_status');

        $this->assertSame(0, SelectionDecision::query()->count());
    }

    public function test_keputusan_mencatat_audit_log(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $registration = $this->makeRegistration($period, $unit);

        $authority = $this->makeUserWithPermission(['set_selection_result']);

        $this->actingAs($authority)->post(route('admin.ppdb.selection.store', $registration), [
            'decision_status' => SelectionDecisionStatus::Accepted->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ppdb.selection.decided',
            'entity_type' => SelectionDecision::class,
            'actor_user_id' => $authority->id,
            'outcome' => 'success',
        ]);
    }
}
