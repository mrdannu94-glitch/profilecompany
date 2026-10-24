<?php

namespace Tests\Feature\Ppdb;

use App\Enums\DocumentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\StorageVisibility;
use App\Http\Middleware\EnsureRegistrationAccess;
use App\Models\Media;
use App\Models\RegistrationDocument;
use App\Models\SchoolUnit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Unggah dan pemeriksaan dokumen pendaftaran.
 */
class DocumentTest extends PpdbScenario
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('ppdb');
    }

    public function test_calon_siswa_bisa_mengunggah_dokumen(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $requirement = $this->makeRequirement($period, $unit);
        $registration = $this->makeRegistration($period, $unit);

        $this->withSession([EnsureRegistrationAccess::SESSION_KEY => $registration->id])
            ->post(route('ppdb.documents.store'), [
                'ppdb_requirement_id' => $requirement->id,
                'document' => UploadedFile::fake()->create('ijazah.pdf', 120, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $document = RegistrationDocument::query()->firstOrFail();

        $this->assertSame(DocumentStatus::Uploaded, $document->status);
        $this->assertSame(1, $document->version_number);
        $this->assertSame($registration->id, $document->registration_id);

        $media = $document->media;

        $this->assertSame(StorageVisibility::Private, $media->storage_visibility);
        $this->assertSame('ppdb', $media->disk);
        $this->assertStringStartsWith('registrations/'.$registration->id.'/', $media->file_path);
        $this->assertStringNotContainsString('ijazah.pdf', $media->file_path);
        Storage::disk('ppdb')->assertExists($media->file_path);
    }

    public function test_berkas_tidak_bisa_diakses_langsung_lewat_url_publik(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $requirement = $this->makeRequirement($period, $unit);
        $registration = $this->makeRegistration($period, $unit);

        $this->withSession([EnsureRegistrationAccess::SESSION_KEY => $registration->id])
            ->post(route('ppdb.documents.store'), [
                'ppdb_requirement_id' => $requirement->id,
                'document' => UploadedFile::fake()->create('ijazah.pdf', 120, 'application/pdf'),
            ]);

        $document = RegistrationDocument::query()->firstOrFail();
        $media = $document->media;

        $this->get('/storage/'.$media->file_path)->assertStatus(403);
    }

    public function test_format_tidak_diaccepted_ditolak(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $requirement = $this->makeRequirement($period, $unit);
        $registration = $this->makeRegistration($period, $unit);

        $this->withSession([EnsureRegistrationAccess::SESSION_KEY => $registration->id])
            ->post(route('ppdb.documents.store'), [
                'ppdb_requirement_id' => $requirement->id,
                'document' => UploadedFile::fake()->create('ijazah.exe', 120, 'application/octet-stream'),
            ])
            ->assertSessionHasErrors('document');

        $this->assertSame(0, RegistrationDocument::query()->count());
        $this->assertSame(0, Media::query()->count());
    }

    public function test_berkas_terlalu_besar_ditolak(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $requirement = $this->makeRequirement($period, $unit, attributes: ['max_file_size' => 1024]);
        $registration = $this->makeRegistration($period, $unit);

        $this->withSession([EnsureRegistrationAccess::SESSION_KEY => $registration->id])
            ->post(route('ppdb.documents.store'), [
                'ppdb_requirement_id' => $requirement->id,
                'document' => UploadedFile::fake()->create('ijazah.pdf', 512, 'application/pdf'),
            ])
            ->assertSessionHasErrors('document');

        $this->assertSame(0, RegistrationDocument::query()->count());
    }

    public function test_persyaratan_dari_unit_lain_ditolak(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $other = SchoolUnit::factory()->create();
        $requirement = $this->makeRequirement($period, $other, attributes: ['requirement_code' => 'IJAZAH-LAIN']);
        $registration = $this->makeRegistration($period, $unit);

        $this->withSession([EnsureRegistrationAccess::SESSION_KEY => $registration->id])
            ->post(route('ppdb.documents.store'), [
                'ppdb_requirement_id' => $requirement->id,
                'document' => UploadedFile::fake()->create('ijazah.pdf', 120, 'application/pdf'),
            ])
            ->assertSessionHasErrors('document');

        $this->assertSame(0, RegistrationDocument::query()->count());
    }

    public function test_unggah_ulang_membuat_versi_baru_dan_tidak_menghapus_riwayat(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $requirement = $this->makeRequirement($period, $unit);
        $registration = $this->makeRegistration($period, $unit);

        $session = [EnsureRegistrationAccess::SESSION_KEY => $registration->id];

        $this->withSession($session)->post(route('ppdb.documents.store'), [
            'ppdb_requirement_id' => $requirement->id,
            'document' => UploadedFile::fake()->create('ijazah.pdf', 120, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $this->withSession($session)->post(route('ppdb.documents.store'), [
            'ppdb_requirement_id' => $requirement->id,
            'document' => UploadedFile::fake()->create('ijazah-revisi.pdf', 120, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, RegistrationDocument::query()->count());
        $this->assertSame([1, 2], RegistrationDocument::query()->orderBy('version_number')->pluck('version_number')->all());
    }

    public function test_petugas_bisa_menyetujui_dokumen(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $requirement = $this->makeRequirement($period, $unit);
        $registration = $this->makeRegistration($period, $unit);
        $document = $this->uploadDocument($registration, $requirement);

        $officer = $this->makeUserWithPermission(['verify_ppdb_documents']);

        $this->actingAs($officer)
            ->post(route('admin.ppdb.documents.review', $document), [
                'action' => 'approve',
                'note' => 'Berkas terbaca.',
            ])
            ->assertSessionHasNoErrors();

        $document->refresh();

        $this->assertSame(DocumentStatus::Approved, $document->status);
        $this->assertSame($officer->id, $document->reviewed_by);
        $this->assertNotNull($document->reviewed_at);
    }

    public function test_dokumen_yang_dikembalikan_wajib_punya_catatan(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $requirement = $this->makeRequirement($period, $unit);
        $registration = $this->makeRegistration($period, $unit);
        $document = $this->uploadDocument($registration, $requirement);

        $officer = $this->makeUserWithPermission(['verify_ppdb_documents']);

        $this->actingAs($officer)
            ->post(route('admin.ppdb.documents.review', $document), ['action' => 'request_revision'])
            ->assertSessionHasErrors('note');

        $this->assertSame(DocumentStatus::Uploaded, $document->fresh()->status);
    }

    public function test_dokumen_dikembalikan_dengan_catatan_koreksi(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $requirement = $this->makeRequirement($period, $unit);
        $registration = $this->makeRegistration($period, $unit);
        $document = $this->uploadDocument($registration, $requirement);

        $officer = $this->makeUserWithPermission(['verify_ppdb_documents']);

        $this->actingAs($officer)
            ->post(route('admin.ppdb.documents.review', $document), [
                'action' => 'request_revision',
                'note' => 'Foto tidak terbaca, mohon unggah ulang.',
            ])
            ->assertSessionHasNoErrors();

        $document->refresh();

        $this->assertSame(DocumentStatus::NeedsRevision, $document->status);
        $this->assertSame('Foto tidak terbaca, mohon unggah ulang.', $document->review_note);
        $this->assertSame(RegistrationStatus::DocumentsRejected, $registration->fresh()->status);
    }

    public function test_semua_dokumen_disetujui_naik_status_pendaftaran(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $requirement = $this->makeRequirement($period, $unit);
        $registration = $this->makeRegistration($period, $unit);
        $document = $this->uploadDocument($registration, $requirement);

        $officer = $this->makeUserWithPermission(['verify_ppdb_documents']);

        $this->actingAs($officer)->post(route('admin.ppdb.documents.review', $document), [
            'action' => 'approve',
        ])->assertSessionHasNoErrors();

        $this->assertSame(RegistrationStatus::DocumentsApproved, $registration->fresh()->status);
    }

    public function test_pengguna_tanpa_permission_tidak_bisa_memeriksa_dokumen(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $requirement = $this->makeRequirement($period, $unit);
        $registration = $this->makeRegistration($period, $unit);
        $document = $this->uploadDocument($registration, $requirement);

        $user = $this->makeUserWithPermission(['view_student_dashboard']);

        $this->actingAs($user)
            ->post(route('admin.ppdb.documents.review', $document), ['action' => 'approve'])
            ->assertForbidden();
    }

    public function test_calon_siswa_tidak_bisa_mengunduh_dokumen_milik_pendaftar_lain(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $requirement = $this->makeRequirement($period, $unit);

        $owner = $this->makeRegistration($period, $unit);
        $other = $this->makeRegistration($period, $unit, attributes: ['candidate_name' => 'Pendaftar Lain']);

        $document = $this->uploadDocument($owner, $requirement);

        $this->withSession([EnsureRegistrationAccess::SESSION_KEY => $other->id])
            ->get(route('ppdb.documents.download', $document))
            ->assertForbidden();
    }

    public function test_calon_siswa_bisa_mengunduh_dokumen_miliknya(): void
    {
        $period = $this->makePeriod();
        $unit = SchoolUnit::factory()->create();
        $requirement = $this->makeRequirement($period, $unit);
        $registration = $this->makeRegistration($period, $unit);
        $document = $this->uploadDocument($registration, $requirement);

        $this->withSession([EnsureRegistrationAccess::SESSION_KEY => $registration->id])
            ->get(route('ppdb.documents.download', $document))
            ->assertOk();
    }

    /**
     * Unggah satu dokumen untuk pendaftaran dan persyaratan tertentu.
     */
    protected function uploadDocument($registration, $requirement): RegistrationDocument
    {
        $this->withSession([EnsureRegistrationAccess::SESSION_KEY => $registration->id])
            ->post(route('ppdb.documents.store'), [
                'ppdb_requirement_id' => $requirement->id,
                'document' => UploadedFile::fake()->create('ijazah.pdf', 120, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        return RegistrationDocument::query()->latest('id')->firstOrFail();
    }
}
