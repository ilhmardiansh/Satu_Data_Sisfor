<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Course;
use App\Models\Lecturer;
use App\Models\NewsArticle;
use App\Models\ProgramProfile;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function index(): View
    {
        $profil = ProgramProfile::query()->first();
        $berita = NewsArticle::query()
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->limit(3)
            ->get();
        $prestasi = Achievement::query()
            ->orderByDesc('tahun')
            ->orderBy('id')
            ->get();
        $dosen = Lecturer::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $kurikulum = Course::query()
            ->orderByDesc('tahun')
            ->orderBy('semester')
            ->orderBy('kode')
            ->get();

        return view('pages.beranda', compact(
            'profil',
            'berita',
            'prestasi',
            'dosen',
            'kurikulum'
        ));
    }
}
