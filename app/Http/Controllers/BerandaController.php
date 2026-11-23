<?php

namespace App\Http\Controllers;

use App\Services\SatuDataService;
use Illuminate\Http\Request;

class BerandaController extends Controller
{
    public function index()
    {
        $profil = SatuDataService::getProfil();
        $categories = SatuDataService::getCategories();
        $allDatasets = SatuDataService::getDatasets();
        $dosen = SatuDataService::getDosen();
        $prestasi = SatuDataService::getPrestasi();
        $kurikulum = SatuDataService::getKurikulum();
        $berita = SatuDataService::getNews();
        $metrics = SatuDataService::getMetrics();

        // Attach dataset count to categories
        foreach ($categories as &$cat) {
            $cat['count'] = count(array_filter($allDatasets, fn($d) => $d['kategori'] === $cat['code']));
        }
        unset($cat);

        $datasetsCount = count($allDatasets);
        $dashboardsCount = count($metrics['visualizations']) + 12;
        $categoriesCount = count($categories);

        return view('pages.beranda', compact(
            'profil',
            'categories',
            'datasetsCount',
            'dashboardsCount',
            'categoriesCount',
            'dosen',
            'prestasi',
            'kurikulum',
            'berita'
        ));
    }
}
