<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DataForm;
use App\Models\Dataset;
use App\Services\DataQualityService;
use App\Services\DatasetBuilderService;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManageDatasetController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $kategori = (string) $request->query('kategori', 'all');
        $tahun = (string) $request->query('tahun', 'all');

        $query = Dataset::query()
            ->with(['category', 'sourceForm'])
            ->orderByDesc('tahun')
            ->orderByDesc('id');

        if ($kategori !== '' && $kategori !== 'all') {
            $query->whereHas('category', function ($sub) use ($kategori): void {
                $sub->where('code', $kategori);
            });
        }

        if ($tahun !== '' && $tahun !== 'all') {
            $query->where('tahun', (string) $tahun);
        }

        if ($q !== '') {
            $like = '%'.strtolower($q).'%';
            $query->whereRaw('LOWER(judul) LIKE ?', [$like]);
        }

        $datasets = $query->paginate(10)->withQueryString();
        $categories = Category::query()->orderBy('id')->get();
        $years = Dataset::query()->distinct()->orderByDesc('tahun')->pluck('tahun');

        $readyFormsCount = DataForm::closed()
            ->where('status_preprocessing', 'siap')
            ->whereHas('submissions', fn ($sub) => $sub->where('status_validasi', 'valid'))
            ->count();

        return view('panel.dataset', compact(
            'datasets',
            'categories',
            'years',
            'q',
            'kategori',
            'tahun',
            'readyFormsCount'
        ));
    }

    public function create(
        Request $request,
        DataQualityService $quality,
        DatasetBuilderService $builder
    ): View {
        $categories = Category::query()->orderBy('id')->get();
        $readyForms = DataForm::closed()
            ->where('status_preprocessing', 'siap')
            ->with(['category', 'kolomFormulir'])
            ->orderByDesc('waktu_diproses')
            ->orderByDesc('created_at')
            ->get();

        $readyFormsPayload = array_values(array_filter(
            $builder->builderFormsPayload($readyForms, $quality),
            fn (array $item): bool => ($item['validCount'] ?? 0) > 0 && ! ($item['dirty'] ?? false)
        ));

        $preselectedFormCode = trim((string) $request->query('form', ''));

        return view('panel.dataset-create', compact(
            'categories',
            'readyFormsPayload',
            'preselectedFormCode'
        ));
    }

    public function storeFromForm(
        Request $request,
        DataQualityService $quality,
        DatasetBuilderService $builder
    ): RedirectResponse {
        $validated = $request->validate([
            'form_id' => ['required', 'integer', 'exists:formulir,id'],
            'mode' => ['required', 'in:rekap,rinci'],
            'group_field_id' => ['required_if:mode,rekap', 'nullable', 'string'],
            'agg_func' => ['nullable', 'in:count,sum,avg,min,max'],
            'agg_field_id' => ['nullable', 'string'],
            'picked_cols' => ['required_if:mode,rinci', 'array'],
            'picked_cols.*' => ['string'],
            'col_aliases' => ['nullable', 'array'],
            'col_aliases.*' => ['nullable', 'string', 'max:120'],
            'masked_cols' => ['nullable', 'array'],
            'masked_cols.*' => ['string'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['required', 'integer', 'exists:kategori,id'],
            'tahun' => ['required', 'digits:4'],
            'periode' => ['required', 'in:Ganjil,Genap'],
            'access' => ['required', 'in:public,private'],
            'publish_status' => ['nullable', 'in:draft,terpublikasi'],
            'format' => ['required', 'in:CSV,XLSX'],
        ], [
            'title.required' => 'Judul wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'tahun.digits' => 'Tahun 4 digit.',
            'picked_cols.required_if' => 'Pilih minimal satu kolom.',
        ]);

        $form = DataForm::query()->with('kolomFormulir')->findOrFail($validated['form_id']);

        if ($form->isOpen()) {
            return back()->withInput()->with(
                'error',
                'Formulir sumber masih dibuka. Tutup formulir dan selesaikan Pre-processing Data sebelum menyusun dataset.'
            );
        }

        if (! $form->isPreprocessed()) {
            return back()->withInput()->with(
                'error',
                'Formulir belum menyelesaikan fase Pre-processing. Jalankan Finalisasi pada menu Pre-processing Data sebelum menyusun dataset.'
            );
        }

        $validSubs = $form->submissions()
            ->with(['user', 'dataIsian.kolomFormulir'])
            ->where('status_validasi', 'valid')
            ->orderByDesc('waktu_pengisian')
            ->get();

        if ($validSubs->isEmpty()) {
            return back()->withInput()->with('error', 'Belum ada baris berstatus valid hasil pre-processing untuk dibuat menjadi dataset.');
        }

        $issuesMap = $quality->inspectSubmissions($form, $validSubs);
        $hasIssues = collect($issuesMap)->contains(fn (array $issues): bool => ! empty($issues));
        if ($hasIssues) {
            return back()->withInput()->with('error', 'Data sumber masih memiliki masalah kualitas. Perbaiki dan finalisasi ulang melalui menu Pre-processing Data.');
        }

        $aggFunc = (string) ($validated['agg_func'] ?? 'count');
        $aggFieldId = ($validated['agg_field_id'] ?? null) ?: null;
        $formFields = collect($form->fields ?? []);
        if ($validated['mode'] === 'rekap' && $aggFunc !== 'count') {
            $numericTarget = $formFields->firstWhere('id', $aggFieldId);
            if (! $numericTarget || ! in_array($numericTarget['type'] ?? '', ['number', 'linear_scale', 'rating'], true)) {
                return back()->withInput()->with('error', 'Pilih kolom bertipe angka untuk fungsi agregasi total, rata-rata, minimum, atau maksimum.');
            }
        }

        $options = [
            'agg_func' => $aggFunc,
            'agg_field_id' => $aggFieldId,
            'col_aliases' => $validated['col_aliases'] ?? [],
            'masked_cols' => $validated['masked_cols'] ?? [],
        ];

        $built = $builder->buildFromForm(
            $form,
            $validSubs,
            $validated['mode'],
            $validated['group_field_id'] ?? null,
            $validated['picked_cols'] ?? [],
            $options
        );

        if (empty($built['cols'])) {
            return back()->withInput()->with('error', 'Pilih minimal satu kolom.');
        }

        $publishStatus = (string) ($validated['publish_status'] ?? 'terpublikasi');

        $dataset = Dataset::create([
            'code' => CodeGenerator::next('DS', 'dataset'),
            'id_kategori' => (int) $validated['category_id'],
            'id_formulir_sumber' => $form->id,
            'judul' => trim($validated['title']),
            'deskripsi' => trim((string) ($validated['description'] ?? '')) ?: "Disusun dari formulir {$form->judul} setelah fase pre-processing.",
            'tahun' => (string) $validated['tahun'],
            'periode' => $validated['periode'],
            'tipe_akses' => $validated['access'],
            'status_publikasi' => $publishStatus,
            'format' => $validated['format'],
            'label_diperbarui' => now()->locale('id')->translatedFormat('j M Y'),
            'kolom' => $built['cols'],
            'baris' => $built['rows'],
            'konfigurasi_sumber' => [
                'mode' => $validated['mode'],
                'group_field_id' => $validated['group_field_id'] ?? null,
                'agg_func' => $aggFunc,
                'agg_field_id' => $aggFieldId,
                'picked_cols' => array_values($validated['picked_cols'] ?? []),
                'col_aliases' => $validated['col_aliases'] ?? [],
                'masked_cols' => array_values($validated['masked_cols'] ?? []),
                'source_form_code' => $form->code,
                'source_form_title' => $form->judul,
                'valid_rows_count' => $validSubs->count(),
                'preprocessed_at' => $form->waktu_diproses?->toIso8601String(),
                'preprocessing_logs_count' => count($form->ringkasan_preprocessing['logs'] ?? []),
            ],
            'dibuat_oleh' => $request->user()->id,
        ]);

        $accessLabel = $dataset->tipe_akses === 'public' ? 'publik' : 'private';
        $pubLabel = $dataset->isPublished() ? 'terpublikasi' : 'draft';

        return redirect()->route('panel.dataset')
            ->with('success', "Dataset “{$dataset->judul}” dibuat ({$pubLabel}, {$accessLabel}).");
    }

    public function toggleAccess(Request $request, Dataset $dataset): JsonResponse|RedirectResponse
    {
        $newAccess = $request->has('access')
            ? ($request->input('access') === 'public' ? 'public' : 'private')
            : ($dataset->tipe_akses === 'public' ? 'private' : 'public');

        $dataset->update([
            'tipe_akses' => $newAccess,
        ]);

        $label = $newAccess === 'public' ? 'publik' : 'private';
        $message = "“{$dataset->judul}” kini {$label}.";

        if ($request->expectsJson()) {
            return response()->json([
                'access' => $newAccess,
                'message' => $message,
            ]);
        }

        return back()->with('info', $message);
    }

    public function togglePublish(Request $request, Dataset $dataset): JsonResponse|RedirectResponse
    {
        $next = $request->has('status_publikasi')
            ? ($request->input('status_publikasi') === 'terpublikasi' ? 'terpublikasi' : 'draft')
            : ($dataset->isPublished() ? 'draft' : 'terpublikasi');

        $dataset->update([
            'status_publikasi' => $next,
        ]);

        $message = $next === 'terpublikasi'
            ? "Dataset “{$dataset->judul}” kini terpublikasi di portal."
            : "Dataset “{$dataset->judul}” dipindahkan ke Draft.";

        if ($request->expectsJson()) {
            return response()->json([
                'status_publikasi' => $next,
                'message' => $message,
            ]);
        }

        return back()->with('info', $message);
    }

    public function syncFromForm(
        Dataset $dataset,
        DataQualityService $quality,
        DatasetBuilderService $builder
    ): RedirectResponse {
        $form = $dataset->sourceForm;
        if (! $form) {
            return back()->with('error', 'Dataset ini tidak memiliki formulir sumber untuk disinkronkan.');
        }

        $form->loadMissing('kolomFormulir');
        if ($form->isOpen()) {
            return back()->with(
                'error',
                "Formulir {$form->judul} masih dibuka. Tutup formulir dan selesaikan Pre-processing Data sebelum menyinkronkan dataset."
            );
        }

        if (! $form->isPreprocessed()) {
            return back()->with(
                'error',
                "Formulir {$form->judul} belum menyelesaikan Finalisasi Pre-processing terbaru. Selesaikan di menu Pre-processing Data terlebih dahulu."
            );
        }

        $validSubs = $form->submissions()
            ->with(['user', 'dataIsian.kolomFormulir'])
            ->where('status_validasi', 'valid')
            ->orderByDesc('waktu_pengisian')
            ->get();

        if ($validSubs->isEmpty()) {
            return back()->with('error', 'Tidak ada baris valid yang tersedia untuk menyinkronkan dataset.');
        }

        $issuesMap = $quality->inspectSubmissions($form, $validSubs);
        if (collect($issuesMap)->contains(fn (array $issues): bool => ! empty($issues))) {
            return back()->with('error', 'Data sumber belum bersih. Perbaiki melalui menu Pre-processing Data sebelum sinkronisasi.');
        }

        $cfg = is_array($dataset->konfigurasi_sumber) ? $dataset->konfigurasi_sumber : [];
        $mode = (string) ($cfg['mode'] ?? 'rinci');
        $pickedCols = is_array($cfg['picked_cols'] ?? null)
            ? $cfg['picked_cols']
            : array_map(fn (array $c): string => $c['id'], $form->sourceCols());

        $built = $builder->buildFromForm(
            $form,
            $validSubs,
            $mode,
            $cfg['group_field_id'] ?? null,
            $pickedCols,
            [
                'agg_func' => $cfg['agg_func'] ?? 'count',
                'agg_field_id' => $cfg['agg_field_id'] ?? null,
                'col_aliases' => $cfg['col_aliases'] ?? [],
                'masked_cols' => $cfg['masked_cols'] ?? [],
            ]
        );

        $cfg['valid_rows_count'] = $validSubs->count();
        $cfg['preprocessed_at'] = $form->waktu_diproses?->toIso8601String();
        $cfg['synced_at'] = now()->toIso8601String();

        $dataset->update([
            'kolom' => $built['cols'],
            'baris' => $built['rows'],
            'label_diperbarui' => now()->locale('id')->translatedFormat('j M Y'),
            'konfigurasi_sumber' => $cfg,
        ]);

        return redirect()->route('panel.dataset')
            ->with('success', "Dataset “{$dataset->judul}” berhasil disinkronkan ({$validSubs->count()} baris valid).");
    }

    public function update(Request $request, Dataset $dataset): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['required', 'integer', 'exists:kategori,id'],
            'tahun' => ['required', 'digits:4'],
            'periode' => ['required', 'in:Ganjil,Genap'],
            'access' => ['required', 'in:public,private'],
            'publish_status' => ['nullable', 'in:draft,terpublikasi'],
            'format' => ['required', 'in:CSV,XLSX'],
        ], [
            'title.required' => 'Judul wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'tahun.digits' => 'Tahun 4 digit.',
        ]);

        $dataset->update([
            'judul' => trim($validated['title']),
            'deskripsi' => trim((string) ($validated['description'] ?? '')),
            'id_kategori' => (int) $validated['category_id'],
            'tahun' => (string) $validated['tahun'],
            'periode' => $validated['periode'],
            'tipe_akses' => $validated['access'],
            'status_publikasi' => (string) ($validated['publish_status'] ?? $dataset->status_publikasi ?? 'terpublikasi'),
            'format' => $validated['format'],
            'label_diperbarui' => now()->locale('id')->translatedFormat('j M Y'),
        ]);

        return redirect()->route('panel.dataset')
            ->with('success', 'Dataset diperbarui.');
    }

    public function destroy(Dataset $dataset): RedirectResponse
    {
        $dataset->delete();

        return redirect()->route('panel.dataset')
            ->with('success', 'Dataset dihapus.');
    }
}
