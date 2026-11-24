<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->withCount(['dataForms', 'datasets'])
            ->orderBy('id')
            ->get();

        return view('panel.kategori', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9-]+$/', 'unique:kategori,code'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'code.required' => 'Kode wajib diisi.',
            'code.regex' => 'Gunakan huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode sudah dipakai.',
            'name.required' => 'Nama kategori wajib diisi.',
        ]);

        Category::create([
            'code' => trim($validated['code']),
            'nama_kategori' => trim($validated['name']),
            'deskripsi' => trim((string) ($validated['description'] ?? '')),
        ]);

        return redirect()->route('panel.kategori')
            ->with('success', 'Kategori disimpan.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:40',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('kategori', 'code')->ignore($category->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'code.required' => 'Kode wajib diisi.',
            'code.regex' => 'Gunakan huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode sudah dipakai.',
            'name.required' => 'Nama kategori wajib diisi.',
        ]);

        $category->update([
            'code' => trim($validated['code']),
            'nama_kategori' => trim($validated['name']),
            'deskripsi' => trim((string) ($validated['description'] ?? '')),
        ]);

        return redirect()->route('panel.kategori')
            ->with('success', 'Kategori disimpan.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $used = $category->dataForms()->count() + $category->datasets()->count();

        if ($used > 0) {
            return back()->with(
                'error',
                "Kategori masih dipakai {$used} formulir/dataset. Pindahkan dulu sebelum menghapus."
            );
        }

        $category->delete();

        return redirect()->route('panel.kategori')
            ->with('success', 'Kategori dihapus.');
    }
}
