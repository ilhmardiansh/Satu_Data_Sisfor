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
            ->whereDoesntHave('datasets')
            ->whereHas('submissions', fn ($sub) => $sub->where('status_validasi', 'valid'))
            ->count();

        $datasetStats = [
            'total' => Dataset::query()->count(),
            'published' => Dataset::published()->count(),
            'public' => Dataset::query()->where('tipe_akses', 'public')->count(),
            'private' => Dataset::query()->where('tipe_akses', 'private')->count(),
        ];

        return view('panel.dataset', compact(
            'datasets',
            'categories',
            'years',
            'q',
            'kategori',
            'tahun',
            'readyFormsCount',
            'datasetStats'
        ));
    }

    public function show(Dataset $dataset): View
    {
        $dataset->loadMissing([
            'category',
            'sourceForm.category',
            'creator',
            'visualizations' => fn ($query) => $query->orderBy('id'),
        ]);

        $cols = array_values($dataset->cols ?? []);
        $rows = array_values($dataset->rows ?? []);
        $numericColumns = $dataset->numericColumns();
        $sourceConfig = is_array($dataset->konfigurasi_sumber) ? $dataset->konfigurasi_sumber : [];
        $pendingRequestsCount = $dataset->requests()->where('status', 'menunggu')->count();
        $approvedRequestsCount = $dataset->requests()->where('status', 'disetujui')->count();

        return view('panel.dataset-show', compact(
            'dataset',
            'cols',
            'rows',
            'numericColumns',
            'sourceConfig',
            'pendingRequestsCount',
            'approvedRequestsCount'
        ));
    }

    public function edit(
        Dataset $dataset,
        DataQualityService $quality,
        DatasetBuilderService $builder
    ): View {
        $dataset->loadMissing(['category', 'sourceForm.kolomFormulir', 'creator', 'visualizations']);
        $categories = Category::query()->orderBy('id')->get();
        $sourceForm = $dataset->sourceForm;
        $sourceFormPayload = null;
        $canRebuildSource = false;

        if ($sourceForm && $sourceForm->isClosed() && $sourceForm->isPreprocessed()) {
            $payload = $builder->builderFormsPayload(collect([$sourceForm]), $quality);
            $candidate = $payload[0] ?? null;
            if ($candidate && ($candidate['validCount'] ?? 0) > 0 && ! ($candidate['dirty'] ?? false)) {
                $sourceFormPayload = $candidate;
                $canRebuildSource = true;
            }
        }

        $sourceConfig = is_array($dataset->konfigurasi_sumber) ? $dataset->konfigurasi_sumber : [];

        return view('panel.dataset-edit', compact(
            'dataset',
            'categories',
            'sourceForm',
            'sourceFormPayload',
            'canRebuildSource',
            'sourceConfig'
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
            ->with(['category', 'kolomFormulir', 'datasets'])
            ->orderByDesc('waktu_diproses')
            ->orderByDesc('created_at')
            ->get();

        $availableForms = $readyForms
            ->filter(fn (DataForm $form): bool => $form->datasets->isEmpty())
            ->values();

        $readyFormsPayload = array_values(array_filter(
            $builder->builderFormsPayload($availableForms, $quality),
            fn (array $item): bool => ($item['validCount'] ?? 0) > 0 && ! ($item['dirty'] ?? false)
        ));

        $alreadyBuiltDatasets = $readyForms
            ->filter(fn (DataForm $form): bool => $form->datasets->isNotEmpty())
            ->map(function (DataForm $form): array {
                $ds = $form->datasets->sortByDesc('id')->first();

                return [
                    'form_code' => $form->code,
                    'form_title' => $form->judul,
                    'dataset_code' => $ds->code,
                    'dataset_title' => $ds->judul,
                    'is_published' => $ds->isPublished(),
                    'access' => $ds->access,
                ];
            })
            ->values();

        $preselectedFormCode = trim((string) $request->query('form', ''));
        $preselectedExistingDataset = $preselectedFormCode !== ''
            ? $alreadyBuiltDatasets->firstWhere('form_code', $preselectedFormCode)
            : null;

        return view('panel.dataset-create', compact(
            'categories',
            'readyFormsPayload',
            'preselectedFormCode',
            'alreadyBuiltDatasets',
            'preselectedExistingDataset'
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

        $existingForForm = Dataset::query()
            ->where('id_formulir_sumber', $form->id)
            ->latest('id')
            ->first();
        if ($existingForForm) {
            return back()->withInput()->with(
                'error',
                "Formulir “{$form->judul}” ({$form->code}) sudah memiliki dataset ({$existingForForm->code}). Ubah, sembunyikan, atau hapus dataset tersebut terlebih dahulu."
            );
        }

        if ($this->hasDuplicateTitleInPeriod(
            (string) $validated['title'],
            (string) $validated['tahun'],
            (string) $validated['periode']
        )) {
            return back()->withInput()->with(
                'error',
                "Dataset dengan judul “{$validated['title']}” untuk {$validated['tahun']} ({$validated['periode']}) sudah ada. Gunakan judul lain atau kelola dataset yang tersedia."
            );
        }

        $builtResult = $this->buildFromValidatedSource($form, $validated, $quality, $builder);
        if (! $builtResult['ok']) {
            return back()->withInput()->with('error', $builtResult['error']);
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
            'kolom' => $builtResult['cols'],
            'baris' => $builtResult['rows'],
            'konfigurasi_sumber' => $builtResult['source_config'],
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
        Request $request,
        Dataset $dataset,
        DataQualityService $quality,
        DatasetBuilderService $builder
    ): RedirectResponse {
        $form = $dataset->sourceForm;
        if (! $form) {
            return back()->with('error', 'Dataset ini tidak memiliki formulir sumber untuk disinkronkan.');
        }

        $form->loadMissing('kolomFormulir');
        $cfg = is_array($dataset->konfigurasi_sumber) ? $dataset->konfigurasi_sumber : [];
        $mode = (string) ($cfg['mode'] ?? 'rinci');
        $pickedCols = is_array($cfg['picked_cols'] ?? null)
            ? $cfg['picked_cols']
            : array_map(fn (array $c): string => $c['id'], $form->sourceCols());

        $builtResult = $this->buildFromValidatedSource($form, [
            'mode' => $mode,
            'group_field_id' => $cfg['group_field_id'] ?? null,
            'agg_func' => $cfg['agg_func'] ?? 'count',
            'agg_field_id' => $cfg['agg_field_id'] ?? null,
            'picked_cols' => $pickedCols,
            'col_aliases' => $cfg['col_aliases'] ?? [],
            'masked_cols' => $cfg['masked_cols'] ?? [],
        ], $quality, $builder);

        if (! $builtResult['ok']) {
            return back()->with('error', $builtResult['error']);
        }

        $nextCfg = $builtResult['source_config'];
        $nextCfg['synced_at'] = now()->toIso8601String();

        $dataset->update([
            'kolom' => $builtResult['cols'],
            'baris' => $builtResult['rows'],
            'label_diperbarui' => now()->locale('id')->translatedFormat('j M Y'),
            'konfigurasi_sumber' => $nextCfg,
        ]);

        $redirectTo = (string) $request->input('redirect_to', 'index');
        $targetRoute = match ($redirectTo) {
            'edit' => route('panel.dataset.edit', $dataset->code),
            'show' => route('panel.dataset.show', $dataset->code),
            default => route('panel.dataset'),
        };

        return redirect($targetRoute)
            ->with('success', "Dataset “{$dataset->judul}” berhasil disinkronkan ({$builtResult['valid_count']} baris valid).");
    }

    public function update(
        Request $request,
        Dataset $dataset,
        DataQualityService $quality,
        DatasetBuilderService $builder
    ): RedirectResponse {
        $rebuild = $request->boolean('rebuild_from_source');

        $rules = [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['required', 'integer', 'exists:kategori,id'],
            'tahun' => ['required', 'digits:4'],
            'periode' => ['required', 'in:Ganjil,Genap'],
            'access' => ['required', 'in:public,private'],
            'publish_status' => ['nullable', 'in:draft,terpublikasi'],
            'format' => ['required', 'in:CSV,XLSX'],
        ];

        if ($rebuild) {
            $rules = array_merge($rules, [
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
            ]);
        }

        $validated = $request->validate($rules, [
            'title.required' => 'Judul wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'tahun.digits' => 'Tahun 4 digit.',
            'picked_cols.required_if' => 'Pilih minimal satu kolom.',
        ]);

        if ($this->hasDuplicateTitleInPeriod(
            (string) $validated['title'],
            (string) $validated['tahun'],
            (string) $validated['periode'],
            $dataset->id
        )) {
            return back()->withInput()->with(
                'error',
                "Dataset dengan judul “{$validated['title']}” untuk {$validated['tahun']} ({$validated['periode']}) sudah ada. Gunakan judul lain."
            );
        }

        $updatePayload = [
            'judul' => trim($validated['title']),
            'deskripsi' => trim((string) ($validated['description'] ?? '')),
            'id_kategori' => (int) $validated['category_id'],
            'tahun' => (string) $validated['tahun'],
            'periode' => $validated['periode'],
            'tipe_akses' => $validated['access'],
            'status_publikasi' => (string) ($validated['publish_status'] ?? $dataset->status_publikasi ?? 'terpublikasi'),
            'format' => $validated['format'],
            'label_diperbarui' => now()->locale('id')->translatedFormat('j M Y'),
        ];

        if ($rebuild) {
            $form = $dataset->sourceForm;
            if (! $form) {
                return back()->withInput()->with('error', 'Dataset ini tidak memiliki formulir sumber untuk disusun ulang.');
            }
            $form->loadMissing('kolomFormulir');
            $builtResult = $this->buildFromValidatedSource($form, $validated, $quality, $builder);
            if (! $builtResult['ok']) {
                return back()->withInput()->with('error', $builtResult['error']);
            }
            $nextCfg = $builtResult['source_config'];
            $nextCfg['synced_at'] = now()->toIso8601String();

            $updatePayload['kolom'] = $builtResult['cols'];
            $updatePayload['baris'] = $builtResult['rows'];
            $updatePayload['konfigurasi_sumber'] = $nextCfg;
        }

        $dataset->update($updatePayload);

        if ($request->input('after_save') === 'stay') {
            return redirect()->route('panel.dataset.edit', $dataset->code)
                ->with('success', "Perubahan pada dataset “{$dataset->judul}” berhasil disimpan.");
        }

        return redirect()->route('panel.dataset')
            ->with('success', 'Dataset diperbarui.');
    }

    public function destroy(Request $request, Dataset $dataset): RedirectResponse
    {
        $title = $dataset->judul;
        $hadSource = $dataset->id_formulir_sumber !== null;
        $dataset->delete();

        $message = $hadSource
            ? "Dataset “{$title}” berhasil dihapus. Formulir sumber kini dapat disusun ulang."
            : "Dataset “{$title}” dihapus.";

        if ($request->input('redirect_to') === 'create') {
            return redirect()->route('panel.dataset.create')
                ->with('success', $message);
        }

        return redirect()->route('panel.dataset')
            ->with('success', $message);
    }

    protected function hasDuplicateTitleInPeriod(
        string $title,
        string $tahun,
        string $periode,
        ?int $ignoreId = null
    ): bool {
        $normalized = mb_strtolower(trim($title));

        return Dataset::query()
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereRaw('LOWER(TRIM(judul)) = ?', [$normalized])
            ->where('tahun', $tahun)
            ->where('periode', $periode)
            ->exists();
    }

    /**
     * Validate a pre-processed source form and build columns, rows, and metadata payload.
     *
     * @param  array<string, mixed>  $params
     * @return array{
     *     ok: bool,
     *     error?: string,
     *     cols?: array<int, string>,
     *     rows?: array<int, array<int, string|int|float>>,
     *     source_config?: array<string, mixed>,
     *     valid_count?: int
     * }
     */
    protected function buildFromValidatedSource(
        DataForm $form,
        array $params,
        DataQualityService $quality,
        DatasetBuilderService $builder
    ): array {
        if ($form->isOpen()) {
            return [
                'ok' => false,
                'error' => 'Formulir sumber masih dibuka. Tutup formulir dan selesaikan Pre-processing Data sebelum menyusun atau menyinkronkan dataset.',
            ];
        }

        if (! $form->isPreprocessed()) {
            return [
                'ok' => false,
                'error' => 'Formulir belum menyelesaikan fase Pre-processing. Jalankan Finalisasi pada menu Pre-processing Data sebelum menyusun dataset.',
            ];
        }

        $validSubs = $form->submissions()
            ->with(['user', 'dataIsian.kolomFormulir'])
            ->where('status_validasi', 'valid')
            ->orderByDesc('waktu_pengisian')
            ->get();

        if ($validSubs->isEmpty()) {
            return [
                'ok' => false,
                'error' => 'Belum ada baris berstatus valid hasil pre-processing untuk disusun menjadi dataset.',
            ];
        }

        $issuesMap = $quality->inspectSubmissions($form, $validSubs);
        if (collect($issuesMap)->contains(fn (array $issues): bool => ! empty($issues))) {
            return [
                'ok' => false,
                'error' => 'Data sumber masih memiliki masalah kualitas. Perbaiki dan finalisasi ulang melalui menu Pre-processing Data.',
            ];
        }

        $mode = (string) ($params['mode'] ?? 'rinci');
        $aggFunc = (string) ($params['agg_func'] ?? 'count');
        $aggFieldId = ($params['agg_field_id'] ?? null) ?: null;
        $formFields = collect($form->fields ?? []);

        if ($mode === 'rekap' && $aggFunc !== 'count') {
            $numericTarget = $formFields->firstWhere('id', $aggFieldId);
            if (! $numericTarget || ! in_array($numericTarget['type'] ?? '', ['number', 'linear_scale', 'rating'], true)) {
                return [
                    'ok' => false,
                    'error' => 'Pilih kolom bertipe angka untuk fungsi agregasi total, rata-rata, minimum, atau maksimum.',
                ];
            }
        }

        $pickedCols = array_values(array_map('strval', $params['picked_cols'] ?? []));
        $colAliases = is_array($params['col_aliases'] ?? null) ? $params['col_aliases'] : [];
        $maskedCols = array_values(array_map('strval', $params['masked_cols'] ?? []));

        $built = $builder->buildFromForm(
            $form,
            $validSubs,
            $mode,
            $params['group_field_id'] ?? null,
            $pickedCols,
            [
                'agg_func' => $aggFunc,
                'agg_field_id' => $aggFieldId,
                'col_aliases' => $colAliases,
                'masked_cols' => $maskedCols,
            ]
        );

        if (empty($built['cols'])) {
            return [
                'ok' => false,
                'error' => 'Pilih minimal satu kolom.',
            ];
        }

        return [
            'ok' => true,
            'cols' => $built['cols'],
            'rows' => $built['rows'],
            'valid_count' => $validSubs->count(),
            'source_config' => [
                'mode' => $mode,
                'group_field_id' => $params['group_field_id'] ?? null,
                'agg_func' => $aggFunc,
                'agg_field_id' => $aggFieldId,
                'picked_cols' => $pickedCols,
                'col_aliases' => $colAliases,
                'masked_cols' => $maskedCols,
                'source_form_code' => $form->code,
                'source_form_title' => $form->judul,
                'valid_rows_count' => $validSubs->count(),
                'preprocessed_at' => $form->waktu_diproses?->toIso8601String(),
                'preprocessing_logs_count' => count($form->ringkasan_preprocessing['logs'] ?? []),
            ],
        ];
    }
}
