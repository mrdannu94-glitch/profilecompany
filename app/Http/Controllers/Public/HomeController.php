<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SchoolUnit;
use App\Services\Cms\PublishedContent;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected readonly PublishedContent $content,
    ) {}

    /**
     * Halaman depan website sekolah.
     */
    public function __invoke(): View
    {
        $units = SchoolUnit::query()
            ->where('is_active', true)
            ->orderBy('level')
            ->get();

        return view('public.home', [
            'units' => $units,
            'page' => $this->content->single('home'),
            'news' => $this->content->items('berita', 3),
            'announcements' => $this->content->items('pengumuman', 3),
            'agenda' => $this->content->items('agenda', 3),
        ]);
    }
}
