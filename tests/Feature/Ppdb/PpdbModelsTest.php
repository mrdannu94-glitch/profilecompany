<?php

namespace Tests\Feature\Ppdb;

use App\Models\Media;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\PpdbPeriod;
use App\Models\PpdbPeriodUnit;
use App\Models\PpdbRequirement;
use App\Models\Program;
use App\Models\Registration;
use App\Models\RegistrationDocument;
use App\Models\SchoolUnit;
use App\Models\SelectionDecision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PpdbModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_map_to_the_tables_created_by_migrations(): void
    {
        $this->assertSame('ppdb_periods', (new PpdbPeriod)->getTable());
        $this->assertSame('ppdb_period_units', (new PpdbPeriodUnit)->getTable());
        $this->assertSame('ppdb_requirements', (new PpdbRequirement)->getTable());
        $this->assertSame('registrations', (new Registration)->getTable());
        $this->assertSame('registration_documents', (new RegistrationDocument)->getTable());
        $this->assertSame('payments', (new Payment)->getTable());
        $this->assertSame('payment_events', (new PaymentEvent)->getTable());
        $this->assertSame('selection_decisions', (new SelectionDecision)->getTable());
    }

    public function test_period_casts_values_to_their_column_types(): void
    {
        $period = $this->makePeriod();

        $this->assertInstanceOf(Carbon::class, $period->registration_start);
        $this->assertInstanceOf(Carbon::class, $period->registration_end);
        $this->assertInstanceOf(Carbon::class, $period->announcement_date);
        $this->assertSame('250000.00', $period->registration_fee);
        $this->assertSame(['channels' => ['bank_transfer']], $period->payment_policy);
    }

    public function test_requirement_and_registration_cast_values_to_their_column_types(): void
    {
        $requirement = $this->makeRequirement(...$this->makeScope());
        $registration = $this->makeRegistration(...$this->makeScope());

        $this->assertSame(2097152, $requirement->max_file_size);
        $this->assertTrue($requirement->is_required);
        $this->assertSame(3, $requirement->sort_order);
        $this->assertTrue($requirement->is_active);

        $this->assertInstanceOf(Carbon::class, $registration->date_of_birth);
        $this->assertInstanceOf(Carbon::class, $registration->submitted_at);
        $this->assertInstanceOf(Carbon::class, $registration->data_consent_at);
    }

    public function test_payment_document_and_decision_cast_values_to_their_column_types(): void
    {
        $payment = $this->makePayment($this->makeRegistration(...$this->makeScope()));
        $document = $this->makeDocument($this->makeRegistration(...$this->makeScope()), $this->makeRequirement(...$this->makeScope()));
        $decision = $this->makeDecision($this->makeRegistration(...$this->makeScope()));

        $this->assertSame('150000.00', $payment->amount);
        $this->assertInstanceOf(Carbon::class, $payment->confirmed_at);

        $this->assertSame(2, $document->version_number);
        $this->assertInstanceOf(Carbon::class, $document->uploaded_at);
        $this->assertInstanceOf(Carbon::class, $document->reviewed_at);

        $this->assertTrue($decision->is_current);
        $this->assertInstanceOf(Carbon::class, $decision->decided_at);
    }

    public function test_registration_hides_sensitive_attributes_from_serialisation(): void
    {
        $registration = $this->makeRegistration(...$this->makeScope());

        $this->assertArrayNotHasKey('access_code_hash', $registration->toArray());
        $this->assertArrayNotHasKey('identity_fingerprint', $registration->toArray());
        $this->assertNotNull($registration->access_code_hash);
    }

    public function test_period_relations_resolve_to_related_records(): void
    {
        [$period, $unit, $program] = $this->makeScope();
        $requirement = $this->makeRequirement($period, $unit, $program);
        $registration = $this->makeRegistration($period, $unit, $program);

        PpdbPeriodUnit::create([
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
        ]);

        $this->assertTrue($period->creator->is(User::find($period->created_by)));
        $this->assertCount(1, $period->schoolUnits);
        $this->assertTrue($period->schoolUnits->first()->is($unit));
        $this->assertCount(1, $period->periodUnits);
        $this->assertCount(1, $period->requirements);
        $this->assertTrue($period->requirements->first()->is($requirement));
        $this->assertCount(1, $period->registrations);
        $this->assertTrue($period->registrations->first()->is($registration));
    }

    public function test_requirement_relations_resolve_to_related_records(): void
    {
        [$period, $unit, $program] = $this->makeScope();
        $requirement = $this->makeRequirement($period, $unit, $program);
        $this->makeDocument($this->makeRegistration($period, $unit, $program), $requirement);

        $this->assertTrue($requirement->period->is($period));
        $this->assertTrue($requirement->schoolUnit->is($unit));
        $this->assertTrue($requirement->program->is($program));
        $this->assertCount(1, $requirement->documents);
    }

    public function test_registration_relations_resolve_to_related_records(): void
    {
        [$period, $unit, $program] = $this->makeScope();
        $registration = $this->makeRegistration($period, $unit, $program);
        $requirement = $this->makeRequirement($period, $unit, $program);

        $registration->documents()->create([
            'ppdb_requirement_id' => $requirement->id,
            'media_id' => $this->makeMedia()->id,
            'version_number' => 1,
            'status' => 'uploaded',
            'uploaded_at' => now(),
        ]);
        $this->makePayment($registration);
        $this->makeDecision($registration);

        $this->assertTrue($registration->period->is($period));
        $this->assertTrue($registration->schoolUnit->is($unit));
        $this->assertTrue($registration->program->is($program));
        $this->assertCount(1, $registration->documents);
        $this->assertCount(1, $registration->payments);
        $this->assertCount(1, $registration->selectionDecisions);
        $this->assertNotNull($registration->currentDecision);
    }

    public function test_document_relations_resolve_to_related_records(): void
    {
        [$period, $unit, $program] = $this->makeScope();
        $document = $this->makeDocument(
            $this->makeRegistration($period, $unit, $program),
            $this->makeRequirement($period, $unit, $program)
        );

        $this->assertNotNull($document->registration);
        $this->assertNotNull($document->requirement);
        $this->assertNotNull($document->media);
        $this->assertNotNull($document->reviewer);
    }

    public function test_payment_and_event_relations_resolve_to_related_records(): void
    {
        [$period, $unit, $program] = $this->makeScope();
        $payment = $this->makePayment($this->makeRegistration($period, $unit, $program));

        $event = PaymentEvent::create([
            'payment_id' => $payment->id,
            'provider' => 'manual',
            'provider_event_id' => 'evt-001',
            'event_type' => 'payment.confirmed',
            'processing_status' => 'processed',
            'received_at' => now(),
        ]);

        $this->assertNotNull($payment->registration);
        $this->assertNotNull($payment->verifier);
        $this->assertCount(1, $payment->events);
        $this->assertTrue($event->payment->is($payment));
    }

    public function test_decision_relations_resolve_to_related_records(): void
    {
        [$period, $unit, $program] = $this->makeScope();
        $decision = $this->makeDecision($this->makeRegistration($period, $unit, $program));

        $this->assertNotNull($decision->registration);
        $this->assertNotNull($decision->decider);
    }

    public function test_period_unit_is_found_by_the_pair_of_keys(): void
    {
        [$period, $unit] = $this->makeScope();

        PpdbPeriodUnit::create([
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
        ]);

        $found = PpdbPeriodUnit::findByPair($period->id, $unit->id);

        $this->assertNotNull($found);
        $this->assertSame($period->id, $found->ppdb_period_id);
        $this->assertSame($unit->id, $found->school_unit_id);
        $this->assertNull(PpdbPeriodUnit::findByPair($period->id, 999999));
    }

    public function test_period_unit_update_only_touches_the_matching_row(): void
    {
        [$period, $unitA] = $this->makeScope();
        $unitB = $this->makeSchoolUnit('SMK-'.uniqid());

        $linkA = PpdbPeriodUnit::create([
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unitA->id,
        ]);
        $linkB = PpdbPeriodUnit::create([
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unitB->id,
        ]);

        $stale = now()->subYear()->startOfSecond();

        $linkA->updated_at = $stale;
        $linkA->save();

        $this->assertTrue($linkA->fresh()->updated_at->equalTo($stale));
        $this->assertTrue($linkB->fresh()->updated_at->greaterThan($stale));
    }

    public function test_period_unit_delete_only_removes_the_matching_row(): void
    {
        [$period, $unitA] = $this->makeScope();
        $unitB = $this->makeSchoolUnit('SMK-'.uniqid());

        $linkA = PpdbPeriodUnit::create([
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unitA->id,
        ]);
        PpdbPeriodUnit::create([
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unitB->id,
        ]);

        $linkA->delete();

        $this->assertSame(1, PpdbPeriodUnit::count());
        $this->assertNull(PpdbPeriodUnit::findByPair($period->id, $unitA->id));
        $this->assertNotNull(PpdbPeriodUnit::findByPair($period->id, $unitB->id));
    }

    /**
     * @return array{0: PpdbPeriod, 1: SchoolUnit, 2: Program}
     */
    private function makeScope(): array
    {
        $period = $this->makePeriod();
        $unit = $this->makeSchoolUnit('UNIT-'.uniqid());
        $program = Program::create([
            'school_unit_id' => $unit->id,
            'code' => 'PRG-'.uniqid(),
            'name' => 'Program Unggulan',
            'is_active' => true,
        ]);

        return [$period, $unit, $program];
    }

    private function makePeriod(): PpdbPeriod
    {
        return PpdbPeriod::forceCreate([
            'code' => 'PPDB-'.uniqid(),
            'name' => 'PPDB 2026',
            'registration_start' => '2026-11-01',
            'registration_end' => '2026-12-31',
            'announcement_date' => '2027-01-15',
            'status' => 'open',
            'registration_fee' => 250000,
            'currency' => 'IDR',
            'payment_policy' => ['channels' => ['bank_transfer']],
            'created_by' => User::factory()->create()->id,
        ]);
    }

    private function makeSchoolUnit(string $code): SchoolUnit
    {
        return SchoolUnit::create([
            'code' => $code,
            'name' => 'Unit '.$code,
            'level' => 'SMP',
            'is_active' => true,
        ]);
    }

    private function makeRequirement(PpdbPeriod $period, SchoolUnit $unit, ?Program $program = null): PpdbRequirement
    {
        return PpdbRequirement::create([
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
            'program_id' => $program?->id,
            'requirement_code' => 'R-'.uniqid(),
            'name' => 'Ijazah',
            'accepted_mime_types' => ['application/pdf', 'image/jpeg'],
            'max_file_size' => 2097152,
            'is_required' => true,
            'sort_order' => 3,
            'is_active' => true,
        ]);
    }

    private function makeRegistration(PpdbPeriod $period, SchoolUnit $unit, ?Program $program = null): Registration
    {
        return Registration::forceCreate([
            'ppdb_period_id' => $period->id,
            'school_unit_id' => $unit->id,
            'program_id' => $program?->id,
            'registration_number' => 'REG-'.uniqid(),
            'candidate_name' => 'Calon Siswa',
            'date_of_birth' => '2011-05-17',
            'parent_name' => 'Orang Tua',
            'parent_phone' => '081200000000',
            'parent_email' => 'ortu@example.test',
            'access_code_hash' => hash('sha256', 'kode-akses'),
            'access_code_expires_at' => now()->addDays(30),
            'identity_fingerprint' => 'fingerprint-'.uniqid(),
            'data_consent_at' => now(),
            'privacy_notice_version' => '1.0',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    private function makeMedia(): Media
    {
        return Media::create([
            'uploaded_by' => User::factory()->create()->id,
            'disk' => 'local',
            'file_path' => 'ppdb/'.uniqid().'.pdf',
            'original_name' => 'ijazah.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 204800,
            'storage_visibility' => 'private',
        ]);
    }

    private function makeDocument(Registration $registration, PpdbRequirement $requirement): RegistrationDocument
    {
        return RegistrationDocument::create([
            'registration_id' => $registration->id,
            'ppdb_requirement_id' => $requirement->id,
            'media_id' => $this->makeMedia()->id,
            'version_number' => 2,
            'status' => 'under_review',
            'reviewed_by' => User::factory()->create()->id,
            'reviewed_at' => now(),
            'review_note' => 'Perlu diperjelas.',
            'uploaded_at' => now(),
        ]);
    }

    private function makePayment(Registration $registration): Payment
    {
        return Payment::create([
            'registration_id' => $registration->id,
            'provider' => 'manual',
            'provider_reference' => 'PAY-'.uniqid(),
            'payment_method' => 'bank_transfer',
            'amount' => 150000,
            'currency' => 'IDR',
            'status' => 'confirmed',
            'expires_at' => now()->addDay(),
            'confirmed_at' => now(),
            'verified_by' => User::factory()->create()->id,
            'verification_method' => 'manual',
            'verification_note' => 'Bukti transfer diterima.',
        ]);
    }

    private function makeDecision(Registration $registration): SelectionDecision
    {
        return SelectionDecision::create([
            'registration_id' => $registration->id,
            'decided_by' => User::factory()->create()->id,
            'decision_status' => 'accepted',
            'decision_note' => 'Lolos seleksi.',
            'decided_at' => now(),
            'is_current' => true,
        ]);
    }
}
