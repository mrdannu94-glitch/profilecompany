<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SchoolUnit;
use Illuminate\View\View;

class HomeController extends Controller
{
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
        ]);
    }
}
