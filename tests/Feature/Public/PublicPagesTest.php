<?php

namespace Tests\Feature\Public;

use App\Enums\ContentStatus;
use App\Models\ContentItem;
use App\Models\ContentRevision;
use App\Models\SchoolUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Seluruh halaman publik harus tetap dapat dibuka, baik sebelum maupun
 * sesudah ada konten terbit, dan hanya menampilkan revisi yang sah.
 */
class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_enam_belas_halaman_publik_dapat_diakses(): void
    {
        $routes = [
            'home',
            'profil',
            'visi-misi',
            'sambutan',
            'guru-staff',
            'program',
            'fasilitas',
            'ekstrakurikuler',
            'prestasi',
            'berita',
            'pengumuman',
            'agenda',
            'galeri',
            'ppdb.info',
            'kontak',
            'faq',
        ];

        foreach ($routes as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_konten_terbit_tampil_dan_draf_tidak_bocor(): void
    {
        $author = User::factory()->create();
        $published = $this->publish($author, 'berita', 'Berita Publik', 'Isi berita publik.');
        $this->draft($author, 'berita', 'Berita Draf');

        $this->get(route('berita'))
            ->assertOk()
            ->assertSee('Berita Publik')
            ->assertDontSee('Berita Draf');

        $this->get(route('berita.show', $published->slug))
            ->assertOk()
            ->assertSee('Berita Publik')
            ->assertSee('Isi berita publik.');

        $this->get(route('berita.show', 'berita-draf'))->assertNotFound();
    }

    public function test_halaman_tunggal_menampilkan_isi_terbit(): void
    {
        $author = User::factory()->create();
        $this->publish($author, 'profil', 'Profil Sekolah', 'Sejarah singkat sekolah.');

        $this->get(route('profil'))
            ->assertOk()
            ->assertSee('Profil Sekolah')
            ->assertSee('Sejarah singkat sekolah.');
    }

    public function test_program_dan_kontak_menampilkan_data_unit(): void
    {
        SchoolUnit::create([
            'code' => 'SMP',
            'name' => 'SMP DMR',
            'level' => 'SMP',
            'address' => 'Jl. Contoh No. 1',
            'phone' => '021000000',
            'email' => 'smp@example.test',
            'is_active' => true,
        ]);

        $this->get(route('program'))->assertOk()->assertSee('SMP DMR');
        $this->get(route('kontak'))->assertOk()->assertSee('Jl. Contoh No. 1');
    }

    protected function publish(User $author, string $type, string $title, string $body): ContentRevision
    {
        $item = ContentItem::create([
            'content_type' => $type,
            'status' => ContentStatus::Published,
        ]);

        $revision = ContentRevision::create([
            'content_item_id' => $item->id,
            'author_id' => $author->id,
            'revision_number' => 1,
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => 'Ringkasan '.$title,
            'body' => $body,
            'workflow_status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        $item->forceFill(['published_revision_id' => $revision->id])->save();

        return $revision;
    }

    protected function draft(User $author, string $type, string $title): ContentRevision
    {
        $item = ContentItem::create([
            'content_type' => $type,
            'status' => ContentStatus::Draft,
        ]);

        return ContentRevision::create([
            'content_item_id' => $item->id,
            'author_id' => $author->id,
            'revision_number' => 1,
            'title' => $title,
            'slug' => Str::slug($title),
            'body' => 'Isi draf.',
            'workflow_status' => ContentStatus::Draft,
        ]);
    }
}
