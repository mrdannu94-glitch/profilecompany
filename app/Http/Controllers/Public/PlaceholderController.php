<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PlaceholderController extends Controller
{
    /**
     * Halaman publik yang belum dibangun pada fase berjalan.
     *
     * Halaman tetap dapat diakses agar navigasi tidak rusak, dan kontennya
     * diganti bertahap sesuai rencana implementasi.
     */
    public function show(string $title, string $phase): View
    {
        return view('public.placeholder', [
            'title' => $title,
            'phase' => $phase,
        ]);
    }
}
