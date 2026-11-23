<?php

namespace App\Http\Controllers;

use App\Services\SatuDataService;
use Illuminate\Http\Request;

class BeritaController extends Controller
{
    public function index(Request $request)
    {
        $berita = SatuDataService::getNews();
        return view('pages.berita', compact('berita'));
    }

    public function show($id)
    {
        $allNews = SatuDataService::getNews();
        $n = null;
        foreach ($allNews as $item) {
            if ($item['id'] === $id) {
                $n = $item;
                break;
            }
        }

        if (!$n) {
            abort(404, 'Berita tidak ditemukan.');
        }

        $related = array_filter($allNews, fn($item) => $item['id'] !== $id);
        $related = array_slice($related, 0, 3);

        return view('pages.berita-detail', compact('n', 'related'));
    }
}
