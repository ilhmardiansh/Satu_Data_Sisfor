<?php

namespace App\Http\Controllers;

use App\Services\SatuDataService;
use Illuminate\Http\Request;

class PengisianDataController extends Controller
{
    public function index(Request $request)
    {
        $allForms = SatuDataService::getForms();
        $categories = SatuDataService::getCategories();

        $target = (string) $request->query('target', 'Semua');
        $q = trim((string) $request->query('q', ''));

        $forms = array_filter($allForms, function ($item) use ($target, $q) {
            if ($target !== 'Semua' && $item['target'] !== $target) {
                return false;
            }
            if ($q !== '') {
                $kw = strtolower($q);
                $titleMatch = str_contains(strtolower($item['title']), $kw);
                $descMatch = str_contains(strtolower($item['desc']), $kw);
                if (!$titleMatch && !$descMatch) {
                    return false;
                }
            }
            return true;
        });

        $activeCount = count(array_filter($allForms, fn($f) => $f['is_active']));
        $targetCount = 3; // Mahasiswa, Dosen, Semua
        $categoryCount = count(array_unique(array_column($allForms, 'kategori')));

        return view('pages.pengisian-data', compact('forms', 'categories', 'target', 'q', 'activeCount', 'targetCount', 'categoryCount'));
    }
}
