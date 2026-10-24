<?php

namespace Tests\Feature\Cms;

use App\Enums\ContentStatus;
use App\Models\ContentItem;
use App\Models\ContentRevision;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Alur kerja CMS: draft -> submit -> review -> publish.
 *
 * Test ini memastikan penulis tidak dapat melewati review dan bahwa versi
 * publik tetap tampil saat revisi baru masih berupa draf.
 */
class ContentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get(route('admin.cms.index'))->assertRedirect(route('login'));
    }

    public function test_guru_dapat_membuat_draf_tetapi_belum_tampil_di_publik(): void
    {
        $teacher = $this->userWithRole('teacher');

        $response = $this->actingAs($teacher)->post(route('admin.cms.store'), [
            'content_type' => 'berita',
            'title' => 'Draf Berita Sekolah',
            'excerpt' => 'Ringkasan berita.',
            'body' => 'Isi berita yang masih draf.',
        ]);

        $item = ContentItem::query()->firstOrFail();

        $response->assertRedirect(route('admin.cms.edit', $item));
        $this->assertSame(ContentStatus::Draft, $item->status);
        $this->assertSame(ContentStatus::Draft, $item->latestRevision->workflow_status);

        $this->get(route('berita'))->assertOk()->assertDontSee('Draf Berita Sekolah');
    }

    public function test_guru_tidak_dapat_mereview_atau_menerbitkan(): void
    {
        $teacher = $this->userWithRole('teacher');
        $item = $this->makeDraft($teacher, 'berita', 'Konten Guru');

        $this->actingAs($teacher)->get(route('admin.cms.review', $item))->assertForbidden();
        $this->actingAs($teacher)->post(route('admin.cms.publish', $item))->assertForbidden();
    }

    public function test_alur_lengkap_review_dan_publikasi(): void
    {
        $teacher = $this->userWithRole('teacher');
        $admin = $this->userWithRole('admin');

        $item = $this->makeDraft($teacher, 'berita', 'Berita Terverifikasi');

        $this->actingAs($teacher)
            ->post(route('admin.cms.submit', $item))
            ->assertRedirect(route('admin.cms.edit', $item));

        $this->assertSame(ContentStatus::Submitted, $item->refresh()->latestRevision->workflow_status);

        $this->actingAs($admin)
            ->post(route('admin.cms.decide', $item), [
                'action' => 'approve',
                'note' => 'Konten sudah sesuai.',
            ])
            ->assertRedirect(route('admin.cms.review', $item));

        $this->assertSame(ContentStatus::Approved, $item->refresh()->latestRevision->workflow_status);

        $this->actingAs($admin)
            ->post(route('admin.cms.publish', $item))
            ->assertRedirect(route('admin.cms.edit', $item));

        $item->refresh();

        $this->assertSame(ContentStatus::Published, $item->status);
        $this->assertNotNull($item->published_revision_id);

        $this->get(route('berita'))->assertOk()->assertSee('Berita Terverifikasi');
        $this->get(route('berita.show', $item->publishedRevision->slug))
            ->assertOk()
            ->assertSee('Berita Terverifikasi');
    }

    public function test_reviewer_dapat_mengembalikan_konten_untuk_revisi(): void
    {
        $teacher = $this->userWithRole('teacher');
        $admin = $this->userWithRole('admin');
        $item = $this->makeDraft($teacher, 'berita', 'Judul Awal');

        $this->actingAs($teacher)->post(route('admin.cms.submit', $item));

        $this->actingAs($admin)
            ->post(route('admin.cms.decide', $item), ['action' => 'request_revision'])
            ->assertSessionHasErrors('note');

        $this->actingAs($admin)
            ->post(route('admin.cms.decide', $item), [
                'action' => 'request_revision',
                'note' => 'Tambahkan sumber.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(ContentStatus::NeedsRevision, $item->refresh()->latestRevision->workflow_status);

        $this->actingAs($teacher)->put(route('admin.cms.update', $item), [
            'title' => 'Judul Revisi',
            'body' => 'Isi yang sudah diperbaiki.',
        ])->assertRedirect(route('admin.cms.edit', $item));

        $this->assertSame('Judul Revisi', $item->refresh()->latestRevision->title);

        $this->actingAs($teacher)->post(route('admin.cms.submit', $item));
        $this->assertSame(ContentStatus::Submitted, $item->refresh()->latestRevision->workflow_status);
    }

    public function test_versi_terbit_tetap_tampil_saat_revisi_baru_masih_draf(): void
    {
        $teacher = $this->userWithRole('teacher');
        $admin = $this->userWithRole('admin');
        $item = $this->makeDraft($teacher, 'berita', 'Versi Lama');

        $this->actingAs($teacher)->post(route('admin.cms.submit', $item));
        $this->actingAs($admin)->post(route('admin.cms.decide', $item), ['action' => 'approve']);
        $this->actingAs($admin)->post(route('admin.cms.publish', $item));

        $publishedSlug = $item->refresh()->publishedRevision->slug;

        $this->actingAs($teacher)->put(route('admin.cms.update', $item), [
            'title' => 'Versi Baru',
            'body' => 'Isi versi baru.',
        ])->assertRedirect(route('admin.cms.edit', $item));

        $item->refresh();

        $this->assertSame(ContentStatus::Draft, $item->status);
        $this->assertCount(2, $item->revisions);
        $this->assertSame($publishedSlug, $item->publishedRevision->slug);

        $this->get(route('berita'))->assertOk()->assertSee('Versi Lama');
        $this->get(route('berita'))->assertDontSee('Versi Baru');

        $this->actingAs($teacher)->post(route('admin.cms.submit', $item));
        $this->actingAs($admin)->post(route('admin.cms.decide', $item), ['action' => 'approve']);
        $this->actingAs($admin)->post(route('admin.cms.publish', $item));

        $this->get(route('berita'))->assertOk()->assertSee('Versi Baru');
    }

    public function test_konten_diarsipkan_hilang_dari_website_publik(): void
    {
        $teacher = $this->userWithRole('teacher');
        $admin = $this->userWithRole('admin');
        $item = $this->makeDraft($teacher, 'pengumuman', 'Pengumuman Terbit');

        $this->actingAs($teacher)->post(route('admin.cms.submit', $item));
        $this->actingAs($admin)->post(route('admin.cms.decide', $item), ['action' => 'approve']);
        $this->actingAs($admin)->post(route('admin.cms.publish', $item));

        $slug = $item->refresh()->publishedRevision->slug;

        $this->actingAs($admin)
            ->post(route('admin.cms.archive', $item))
            ->assertRedirect(route('admin.cms.index'));

        $this->get(route('pengumuman'))->assertOk()->assertDontSee('Pengumuman Terbit');
        $this->get(route('pengumuman.show', $slug))->assertNotFound();
    }

    public function test_halaman_tunggal_tidak_dapat_dibuat_dua_kali(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->post(route('admin.cms.store'), [
            'content_type' => 'profil',
            'title' => 'Profil Sekolah',
            'body' => 'Isi profil.',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.cms.store'), [
            'content_type' => 'profil',
            'title' => 'Profil Kedua',
            'body' => 'Duplikat.',
        ])->assertSessionHasErrors('content_type');

        $this->assertSame(1, ContentItem::query()->where('content_type', 'profil')->count());
    }

    protected function userWithRole(string $role): User
    {
        $user = User::factory()->create();

        $user->roles()->attach(Role::query()->where('name', $role)->firstOrFail());

        return $user;
    }

    protected function makeDraft(User $author, string $type, string $title): ContentItem
    {
        $item = ContentItem::create([
            'content_type' => $type,
            'status' => ContentStatus::Draft,
        ]);

        ContentRevision::create([
            'content_item_id' => $item->id,
            'author_id' => $author->id,
            'revision_number' => 1,
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => 'Ringkasan '.$title,
            'body' => 'Isi konten '.$title,
            'workflow_status' => ContentStatus::Draft,
        ]);

        return $item;
    }
}
