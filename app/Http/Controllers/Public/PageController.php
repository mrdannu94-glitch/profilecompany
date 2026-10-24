<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SchoolUnit;
use App\Services\Cms\PublishedContent;
use Illuminate\View\View;

/**
 * Halaman publik yang isinya dikelola melalui CMS.
 *
 * Halaman tetap dapat dibuka meski belum ada konten terbit; tampilan
 * memberikan keterangan bahwa konten belum tersedia alih-alih menampilkan
 * data karangan.
 */
class PageController extends Controller
{
    public function __construct(
        protected readonly PublishedContent $content,
    ) {}

    public function profil(): View
    {
        return view('public.pages.show', [
            'page' => $this->content->single('profil'),
            'title' => 'Profil Sekolah',
            'description' => 'Sejarah, identitas, dan unit sekolah dalam satu yayasan.',
            'units' => $this->units(),
        ]);
    }

    public function visiMisi(): View
    {
        return view('public.pages.show', [
            'page' => $this->content->single('visi_misi'),
            'title' => 'Visi & Misi',
            'description' => 'Arah dan komitmen pendidikan sekolah.',
        ]);
    }

    public function sambutan(): View
    {
        return view('public.pages.show', [
            'page' => $this->content->single('sambutan'),
            'title' => 'Sambutan Kepala Sekolah',
            'description' => 'Pesan dari kepala sekolah untuk siswa, orang tua, dan masyarakat.',
        ]);
    }

    public function guruStaff(): View
    {
        return view('public.pages.list', [
            'entries' => $this->content->items('guru_staff'),
            'title' => 'Guru & Staff',
            'eyebrow' => 'Sumber Daya',
            'description' => 'Profil guru dan tenaga kependidikan yang telah disetujui untuk ditampilkan.',
        ]);
    }

    public function program(): View
    {
        return view('public.pages.program', [
            'entries' => $this->content->items('program'),
            'units' => $this->units(),
        ]);
    }

    public function fasilitas(): View
    {
        return view('public.pages.list', [
            'entries' => $this->content->items('fasilitas'),
            'title' => 'Fasilitas',
            'eyebrow' => 'Sarana',
            'description' => 'Sarana dan prasarana pendukung kegiatan belajar.',
        ]);
    }

    public function ekstrakurikuler(): View
    {
        return view('public.pages.list', [
            'entries' => $this->content->items('ekstrakurikuler'),
            'title' => 'Ekstrakurikuler',
            'eyebrow' => 'Kegiatan Siswa',
            'description' => 'Kegiatan pengembangan bakat dan minat siswa.',
        ]);
    }

    public function prestasi(): View
    {
        return view('public.pages.list', [
            'entries' => $this->content->items('prestasi'),
            'title' => 'Prestasi',
            'eyebrow' => 'Pencapaian',
            'description' => 'Prestasi siswa dan sekolah yang terdokumentasi.',
        ]);
    }

    public function kontak(): View
    {
        return view('public.pages.kontak', [
            'page' => $this->content->single('kontak'),
            'units' => $this->units(),
        ]);
    }

    public function faq(): View
    {
        return view('public.pages.faq', [
            'entries' => $this->content->items('faq'),
        ]);
    }

    /**
     * Unit aktif beserta programnya untuk halaman profil, program, dan kontak.
     */
    protected function units()
    {
        return SchoolUnit::query()
            ->where('is_active', true)
            ->with(['programs' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->orderBy('level')
            ->get();
    }
}
