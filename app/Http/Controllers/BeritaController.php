<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BeritaController extends Controller
{
    public function index(Request $request): View
    {
        $selectedTag = (string) $request->query('tag', 'Semua');
        $q = trim((string) $request->query('q', ''));

        $query = NewsArticle::query()->orderByDesc('created_at')->orderBy('id');

        if ($selectedTag !== '' && $selectedTag !== 'Semua') {
            $query->where('badge', $selectedTag);
        }

        if ($q !== '') {
            $like = '%'.strtolower($q).'%';
            $query->where(function ($sub) use ($like): void {
                $sub->whereRaw('LOWER(title) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(excerpt) LIKE ?', [$like]);
            });
        }

        $berita = $query->get();
        $tags = array_merge(['Semua'], NewsArticle::TAGS);

        return view('pages.berita', compact('berita', 'tags', 'selectedTag', 'q'));
    }

    public function show(string $code): View
    {
        $article = NewsArticle::query()->where('code', $code)->firstOrFail();
        $related = NewsArticle::query()
            ->where('id', '!=', $article->id)
            ->orderByDesc('created_at')
            ->orderBy('id')
            ->limit(3)
            ->get();

        return view('pages.berita-detail', [
            'n' => $article,
            'related' => $related,
        ]);
    }
}
