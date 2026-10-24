<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ContentItem;
use App\Services\Cms\PublishedContent;
use Illuminate\View\View;

/**
 * Daftar dan detail konten publik (berita, pengumuman, agenda, galeri).
 *
 * Setiap jenis memiliki method eksplisit agar jenis konten tidak ditentukan
 * oleh parameter rute yang dapat dimanipulasi pengunjung.
 */
class ContentController extends Controller
{
    public function __construct(
        protected readonly PublishedContent $content,
    ) {}

    public function news(): View
    {
        return $this->renderIndex('berita');
    }

    public function newsShow(string $slug): View
    {
        return $this->renderShow('berita', $slug);
    }

    public function announcements(): View
    {
        return $this->renderIndex('pengumuman');
    }

    public function announcementShow(string $slug): View
    {
        return $this->renderShow('pengumuman', $slug);
    }

    public function agenda(): View
    {
        return $this->renderIndex('agenda');
    }

    public function agendaShow(string $slug): View
    {
        return $this->renderShow('agenda', $slug);
    }

    public function gallery(): View
    {
        return view('public.content.gallery', [
            'typeLabel' => ContentItem::TYPES['galeri'],
            'entries' => $this->content->items('galeri'),
        ]);
    }

    public function galleryShow(string $slug): View
    {
        return $this->renderShow('galeri', $slug);
    }

    protected function renderIndex(string $type): View
    {
        return view('public.content.index', [
            'type' => $type,
            'typeLabel' => ContentItem::TYPES[$type],
            'entries' => $this->content->items($type),
        ]);
    }

    protected function renderShow(string $type, string $slug): View
    {
        $entry = $this->content->find($type, $slug);

        abort_if($entry === null, 404);

        return view('public.content.show', [
            'type' => $type,
            'typeLabel' => ContentItem::TYPES[$type],
            'entry' => $entry,
        ]);
    }
}
