<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Category;
use App\Models\Course;
use App\Models\Dataset;
use App\Models\Lecturer;
use App\Models\NewsArticle;
use App\Models\ProgramProfile;
use App\Models\Visualization;
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
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();
        $kurikulum = Course::query()
            ->orderByDesc('tahun')
            ->orderBy('semester')
            ->orderBy('kode_mk')
            ->get();
        $categories = Category::query()
            ->orderBy('id')
            ->get(['id', 'code', 'nama_kategori']);

        $shownVisualizationsCount = Visualization::datasetCharts()
            ->with('dataset')
            ->get()
            ->filter(fn (Visualization $v): bool => $v->show && ($v->dataset?->isPublished() ?? false) && $v->dataset?->tipe_akses === 'public')
            ->count();

        $heroStats = [
            'datasets' => Dataset::published()->count(),
            'dashboards' => $shownVisualizationsCount,
            'categories' => $categories->count(),
        ];

        return view('pages.beranda', compact(
            'profil',
            'berita',
            'prestasi',
            'dosen',
            'kurikulum',
            'categories',
            'heroStats'
        ));
    }
}
