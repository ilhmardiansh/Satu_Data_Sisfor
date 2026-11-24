<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DataForm;
use App\Models\FormSubmission;
use App\Models\SubmissionValue;
use App\Services\DataPreprocessingService;
use App\Services\DataQualityService;
use App\Services\DatasetBuilderService;
use App\Services\SpreadsheetExportService;
use App\Services\SpreadsheetImportService;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CollectedDataController extends Controller
{
    public function index(
        Request $request,
        DataQualityService $quality,
        DataPreprocessingService $preprocessing,
        DatasetBuilderService $builder
    ): View {
        $formCode = trim((string) $request->query('form', ''));
        if ($formCode !== '') {
            $selectedForm = DataForm::query()
                ->with(['category', 'kolomFormulir', 'processor'])
                ->where('code', $formCode)
                ->first();

            if ($selectedForm) {
                return $this->renderWorkspace($request, $selectedForm, $quality, $preprocessing, $builder);
            }
        }

        $q = trim((string) $request->query('q', ''));
        $kategori = (string) $request->query('kategori', 'all');
        $tahun = (string) $request->query('tahun', 'all');
        $periode = (string) $request->query('periode', 'all');
        if (! in_array($periode, ['all', 'Ganjil', 'Genap'], true)) {
            $periode = 'all';
        }
        $stage = (string) $request->query('stage', 'all');
        if (! in_array($stage, ['all', 'needs_attention', 'ready', 'empty'], true)) {
            $stage = 'all';
        }
        $sort = (string) $request->query('sort', 'priority');
        if (! in_array($sort, ['priority', 'latest', 'responses', 'title'], true)) {
            $sort = 'priority';
        }

        $allForms = DataForm::query()
            ->with(['category', 'kolomFormulir', 'processor'])
            ->withCount([
                'submissions',
                'submissions as raw_submissions_count' => fn ($sq) => $sq->where('status_validasi', 'mentah'),
                'submissions as valid_submissions_count' => fn ($sq) => $sq->where('status_validasi', 'valid'),
                'submissions as rejected_submissions_count' => fn ($sq) => $sq->where('status_validasi', 'ditolak'),
            ])
            ->orderByDesc('created_at')
            ->get();

        $stageOf = static function (DataForm $f): string {
            if ($f->status_preprocessing === 'siap') {
                return 'ready';
            }
            if ($f->status_preprocessing === 'proses' || (int) $f->submissions_count > 0) {
                return 'needs_attention';
            }

            return 'empty';
        };

        $summary = [
            'total' => $allForms->count(),
            'needs_attention' => $allForms->filter(fn (DataForm $f): bool => $stageOf($f) === 'needs_attention')->count(),
            'ready' => $allForms->filter(fn (DataForm $f): bool => $stageOf($f) === 'ready')->count(),
            'empty' => $allForms->filter(fn (DataForm $f): bool => $stageOf($f) === 'empty')->count(),
        ];

        $kw = mb_strtolower($q);
        $filteredForms = $allForms->filter(function (DataForm $f) use ($kw, $kategori, $tahun, $periode, $stage, $stageOf): bool {
            if ($kategori !== 'all' && $f->category?->code !== $kategori) {
                return false;
            }
            if ($tahun !== 'all' && (string) $f->tahun !== $tahun) {
                return false;
            }
            if ($periode !== 'all' && (string) $f->periode !== $periode) {
                return false;
            }
            if ($stage !== 'all' && $stageOf($f) !== $stage) {
                return false;
            }
            if ($kw !== '') {
                $haystack = mb_strtolower(implode(' ', [
                    $f->code,
                    $f->judul,
                    (string) ($f->deskripsi ?? ''),
                    (string) ($f->category?->nama_kategori ?? ''),
                    (string) $f->tahun,
                    (string) $f->target,
                ]));
                if (! str_contains($haystack, $kw)) {
                    return false;
                }
            }

            return true;
        })->values();

        if ($sort === 'responses') {
            $filteredForms = $filteredForms->sortByDesc('submissions_count')->values();
        } elseif ($sort === 'title') {
            $filteredForms = $filteredForms->sortBy(fn (DataForm $f): string => mb_strtolower($f->judul))->values();
        } elseif ($sort === 'priority') {
            $rankMap = ['needs_attention' => 0, 'ready' => 1, 'empty' => 2];
            $filteredForms = $filteredForms->sort(function (DataForm $a, DataForm $b) use ($rankMap, $stageOf): int {
                $ra = $rankMap[$stageOf($a)] ?? 9;
                $rb = $rankMap[$stageOf($b)] ?? 9;
                if ($ra !== $rb) {
                    return $ra <=> $rb;
                }
                if ((int) $a->raw_submissions_count !== (int) $b->raw_submissions_count) {
                    return (int) $b->raw_submissions_count <=> (int) $a->raw_submissions_count;
                }
                if ((int) $a->submissions_count !== (int) $b->submissions_count) {
                    return (int) $b->submissions_count <=> (int) $a->submissions_count;
                }

                return ($b->created_at?->timestamp ?? 0) <=> ($a->created_at?->timestamp ?? 0);
            })->values();
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 12;
        $formsPaginator = new LengthAwarePaginator(
            $filteredForms->forPage($page, $perPage)->values(),
            $filteredForms->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $categories = Category::query()->orderBy('id')->get();
        $usedCategoryIds = $allForms->pluck('id_kategori')->unique()->all();
        $filterCategories = $categories->whereIn('id', $usedCategoryIds)->values();
        $years = $allForms->pluck('tahun')->unique()->sortDesc()->values();

        return view('panel.preprocessing-index', compact(
            'formsPaginator',
            'summary',
            'filterCategories',
            'years',
            'q',
            'kategori',
            'tahun',
            'periode',
            'stage',
            'sort'
        ));
    }

    public function show(
        Request $request,
        DataForm $dataForm,
        DataQualityService $quality,
        DataPreprocessingService $preprocessing,
        DatasetBuilderService $builder
    ): View {
        $dataForm->loadMissing(['category', 'kolomFormulir', 'processor']);

        return $this->renderWorkspace($request, $dataForm, $quality, $preprocessing, $builder);
    }

    protected function renderWorkspace(
        Request $request,
        DataForm $activeForm,
        DataQualityService $quality,
        DataPreprocessingService $preprocessing,
        DatasetBuilderService $builder
    ): View {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', $request->boolean('only_issues') ? 'issues' : 'all');
        if (! in_array($status, ['all', 'mentah', 'valid', 'ditolak', 'issues'], true)) {
            $status = 'all';
        }
        $onlyIssues = $status === 'issues';

        $allSubs = $activeForm->submissions()
            ->with(['user', 'cleaner', 'dataIsian.kolomFormulir'])
            ->orderByDesc('waktu_pengisian')
            ->orderByDesc('id')
            ->get();

        $qualityIssues = $quality->inspectSubmissions($activeForm, $allSubs);
        $profile = $preprocessing->profileForm($activeForm, $allSubs, $qualityIssues);
        $problemCount = (int) $profile['issue_rows'];
        $totalSubmissionsCount = (int) $profile['total'];

        $logs = $activeForm->ringkasan_preprocessing['logs'] ?? [];
        $preprocessingLogs = is_array($logs) ? array_reverse($logs) : [];

        $kw = strtolower($q);
        $filteredSubs = $allSubs->filter(function (FormSubmission $sub) use ($activeForm, $qualityIssues, $status, $kw): bool {
            if ($status === 'issues' && empty($qualityIssues[$sub->id])) {
                return false;
            }
            if (in_array($status, ['mentah', 'valid', 'ditolak'], true) && $sub->status_validasi !== $status) {
                return false;
            }

            if ($kw !== '') {
                $cells = [
                    $sub->code,
                    $sub->user?->name ?? '-',
                    $sub->user?->nim ?? '-',
                    $sub->submittedFormatted(),
                ];
                foreach ($activeForm->fields ?? [] as $field) {
                    $cells[] = (string) ($sub->values[$field['id']] ?? '');
                }
                if (! str_contains(strtolower(implode(' ', $cells)), $kw)) {
                    return false;
                }
            }

            return true;
        })->values();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 15;
        $submissionsPaginator = new LengthAwarePaginator(
            $filteredSubs->forPage($page, $perPage)->values(),
            $filteredSubs->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $allForms = DataForm::query()
            ->with(['category', 'kolomFormulir', 'processor'])
            ->withCount('submissions')
            ->orderByDesc('created_at')
            ->get();
        $categories = Category::query()->orderBy('id')->get();
        $builderFormsPayload = $builder->builderFormsPayload($allForms, $quality);

        return view('panel.data', compact(
            'categories',
            'activeForm',
            'submissionsPaginator',
            'qualityIssues',
            'profile',
            'preprocessingLogs',
            'problemCount',
            'totalSubmissionsCount',
            'q',
            'status',
            'onlyIssues',
            'builderFormsPayload'
        ));
    }

    public function exportCsv(DataForm $dataForm, SpreadsheetExportService $exporter): StreamedResponse
    {
        $dataForm->loadMissing('kolomFormulir');
        $subs = $dataForm->submissions()
            ->with(['user', 'dataIsian.kolomFormulir'])
            ->orderByDesc('waktu_pengisian')
            ->get();

        $sourceCols = $dataForm->sourceCols();
        $cols = array_merge(
            array_map(fn (array $c): string => $c['label'], $sourceCols),
            ['Status Validasi']
        );
        $rows = [];

        foreach ($subs as $sub) {
            $row = [
                $sub->user?->name ?? '-',
                $sub->user?->nim ?? '-',
                $sub->submittedFormatted(),
            ];
            foreach ($dataForm->fields ?? [] as $field) {
                $row[] = (string) ($sub->values[$field['id']] ?? '');
            }
            $row[] = $sub->status_validasi;
            $rows[] = $row;
        }

        return $exporter->downloadCsv($dataForm->judul, $cols, $rows);
    }

    public function importResponses(
        Request $request,
        DataForm $dataForm,
        SpreadsheetImportService $importer,
        DataQualityService $quality,
        DataPreprocessingService $preprocessing
    ): RedirectResponse {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ], [
            'file.required' => 'Pilih berkas CSV atau XLSX hasil respons formulir.',
            'file.mimes' => 'Format berkas harus CSV atau XLSX.',
            'file.max' => 'Ukuran berkas maksimal 10 MB.',
        ]);

        $uploadedFile = $request->file('file');
        $realPath = $uploadedFile?->getRealPath();
        if (! is_string($realPath) || $realPath === '') {
            return back()->with('error', 'Berkas gagal dibaca.');
        }

        try {
            $parsed = $importer->parseUploadedFile(
                $realPath,
                (string) $uploadedFile->getClientOriginalExtension()
            );
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal memproses berkas: '.$e->getMessage());
        }

        $dataForm->loadMissing('kolomFormulir');
        $mappedRows = $importer->mapRowsToForm($dataForm, $parsed['headers'], $parsed['rows']);

        if (empty($mappedRows)) {
            return back()->with('error', 'Berkas tidak memiliki baris respons yang dapat diimpor.');
        }

        $userId = $request->user()->id;
        $fields = $dataForm->kolomFormulir
            ->filter(fn ($col): bool => $col->isInputField())
            ->values();

        DB::transaction(function () use ($dataForm, $fields, $mappedRows, $userId, $quality, $preprocessing): void {
            $now = now();
            $submissionInserts = [];
            $valuesByCode = [];

            foreach ($mappedRows as $idx => $rowValues) {
                $subCode = CodeGenerator::next('S', 'pengisian_formulir').'-'.($idx + 1);
                $submissionInserts[] = [
                    'code' => $subCode,
                    'id_formulir' => $dataForm->id,
                    'diisi_oleh' => $userId,
                    'waktu_pengisian' => $now,
                    'status_validasi' => 'mentah',
                    'sumber_input' => 'impor',
                    'jalur_berkas_bukti' => null,
                    'dibersihkan_oleh' => null,
                    'waktu_dibersihkan' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $valuesByCode[$subCode] = $rowValues;
            }

            foreach (array_chunk($submissionInserts, 100) as $chunk) {
                FormSubmission::query()->insert($chunk);
            }

            $insertedIds = FormSubmission::query()
                ->where('id_formulir', $dataForm->id)
                ->whereIn('code', array_keys($valuesByCode))
                ->pluck('id', 'code')
                ->all();

            $valueRows = [];
            foreach ($valuesByCode as $subCode => $rowValues) {
                $subId = $insertedIds[$subCode] ?? null;
                if ($subId === null) {
                    continue;
                }

                foreach ($fields as $col) {
                    $kode = $col->kode_kolom;
                    $raw = (string) ($rowValues[$kode] ?? '');
                    $fieldDef = $col->toFieldArray();
                    $isClean = $quality->fieldError($fieldDef, $raw) === null
                        && ($raw === '' || $quality->normalizeValue($fieldDef, $raw) === $raw);

                    $valueRows[] = [
                        'id_pengisian' => $subId,
                        'id_kolom' => $col->id,
                        'nilai' => $raw,
                        'nilai_mentah' => $raw,
                        'status_bersih' => $isClean,
                        'jalur_berkas' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($valueRows, 100) as $chunk) {
                SubmissionValue::query()->insert($chunk);
            }

            $preprocessing->markFormNeedsPreprocessing($dataForm);
        });

        $importedCount = count($mappedRows);

        return redirect()
            ->route('panel.preprocessing', ['form' => $dataForm->code])
            ->with('success', "{$importedCount} baris respons diimpor sebagai data mentah dan siap melalui fase Pre-processing.");
    }

    public function normalizeBatch(
        Request $request,
        DataForm $dataForm,
        DataPreprocessingService $preprocessing
    ): RedirectResponse {
        $validated = $request->validate([
            'normalize_numbers' => ['nullable', 'boolean'],
            'text_case' => ['nullable', 'in:none,title,upper,lower'],
        ]);

        $res = $preprocessing->normalizeForm($dataForm, $request->user()->id, [
            'normalize_numbers' => $request->boolean('normalize_numbers'),
            'text_case' => (string) ($validated['text_case'] ?? 'none'),
        ]);

        $msg = $res['changed_cells'] > 0
            ? "Normalisasi selesai: {$res['changed_cells']} sel pada {$res['changed_rows']} baris diseragamkan."
            : 'Normalisasi dijalankan. Seluruh nilai sudah seragam.';

        return redirect()
            ->route('panel.preprocessing', ['form' => $dataForm->code])
            ->with('success', $msg);
    }

    public function deduplicateBatch(
        Request $request,
        DataForm $dataForm,
        DataPreprocessingService $preprocessing
    ): RedirectResponse {
        $validated = $request->validate([
            'scope' => ['required', 'in:same_values,same_user_and_values'],
            'action' => ['required', 'in:reject,delete'],
        ]);

        $res = $preprocessing->deduplicateForm(
            $dataForm,
            $request->user()->id,
            $validated['scope'],
            $validated['action']
        );

        if ($res['handled'] === 0) {
            return redirect()
                ->route('panel.preprocessing', ['form' => $dataForm->code])
                ->with('info', 'Tidak ditemukan baris duplikat pada respons aktif.');
        }

        $label = $validated['action'] === 'delete' ? 'dihapus' : 'ditandai ditolak';

        return redirect()
            ->route('panel.preprocessing', ['form' => $dataForm->code])
            ->with('success', "Deduplikasi selesai: {$res['handled']} baris ganda {$label}.");
    }

    public function imputeBatch(
        Request $request,
        DataForm $dataForm,
        DataPreprocessingService $preprocessing
    ): RedirectResponse {
        $validated = $request->validate([
            'field_code' => ['required', 'string', 'max:60'],
            'strategy' => ['required', 'in:mean,median,mode,constant,reject_missing'],
            'custom_value' => ['nullable', 'string', 'max:500'],
        ]);

        $res = $preprocessing->imputeColumn(
            $dataForm,
            $request->user()->id,
            $validated['field_code'],
            $validated['strategy'],
            $validated['custom_value'] ?? null
        );

        if (! $res['ok']) {
            return redirect()
                ->route('panel.preprocessing', ['form' => $dataForm->code])
                ->with('error', $res['message']);
        }

        return redirect()
            ->route('panel.preprocessing', ['form' => $dataForm->code])
            ->with('success', $res['message']);
    }

    public function finalizePreprocessing(
        Request $request,
        DataForm $dataForm,
        DataPreprocessingService $preprocessing
    ): RedirectResponse {
        $res = $preprocessing->finalizeForm($dataForm, $request->user()->id);

        if (! $res['ok']) {
            return redirect()
                ->route('panel.preprocessing', ['form' => $dataForm->code])
                ->with('error', $res['message']);
        }

        return redirect()
            ->route('panel.preprocessing', ['form' => $dataForm->code])
            ->with('success', $res['message']);
    }

    public function editSubmission(
        FormSubmission $submission,
        DataQualityService $quality
    ): View {
        $submission->loadMissing([
            'dataForm.kolomFormulir',
            'dataForm.category',
            'user',
            'cleaner',
            'dataIsian.kolomFormulir',
        ]);

        $form = $submission->dataForm;
        if (! $form) {
            abort(404, 'Formulir untuk isian ini tidak ditemukan.');
        }

        $fields = $form->fields ?? [];
        $values = $submission->values ?? [];
        $rawValues = $submission->rawValues ?? [];
        $attachments = $submission->attachments ?? [];

        $allSubs = $form->submissions()
            ->with(['user', 'dataIsian.kolomFormulir'])
            ->get();
        $allIssues = $quality->inspectSubmissions($form, $allSubs);
        $issues = $allIssues[$submission->id] ?? [];

        $attachmentMeta = [];
        foreach ($fields as $fld) {
            if (($fld['type'] ?? '') !== 'file') {
                continue;
            }
            $fid = (string) ($fld['id'] ?? '');
            $path = $attachments[$fid] ?? null;
            $exists = is_string($path) && $path !== '' && Storage::disk('local')->exists($path);
            $size = $exists ? (int) Storage::disk('local')->size($path) : 0;
            $attachmentMeta[$fid] = [
                'path' => is_string($path) ? $path : '',
                'name' => trim((string) ($values[$fid] ?? '')) ?: ($exists ? basename($path) : ''),
                'exists' => $exists,
                'size' => $size,
                'valid_file' => $exists && $size > 0,
                'download_url' => $exists && $size > 0 ? route('panel.submissions.file', [$submission->code, $fid]) : null,
                'preview_url' => $exists && $size > 0 ? route('panel.submissions.file', [$submission->code, $fid, 'inline' => 1]) : null,
            ];
        }

        $oldUploadedTokens = [];
        $oldUploadedLabels = [];
        $rawOldTokens = old('uploaded_tokens', []);
        if (is_array($rawOldTokens)) {
            foreach ($rawOldTokens as $fid => $tokRaw) {
                $tokStr = trim((string) $tokRaw);
                if ($tokStr === '' || $tokStr === '[]') {
                    continue;
                }
                $decodedList = json_decode($tokStr, true);
                $list = is_array($decodedList) ? $decodedList : [$tokStr];
                $labels = [];
                $valid = true;
                foreach ($list as $tok) {
                    try {
                        $meta = json_decode(Crypt::decryptString((string) $tok), true);
                    } catch (Throwable) {
                        $meta = null;
                    }
                    if (! is_array($meta) || empty($meta['name']) || (int) ($meta['size'] ?? 0) <= 0) {
                        $valid = false;
                        break;
                    }
                    $labels[] = (string) $meta['name'];
                }
                if ($valid && ! empty($labels)) {
                    $oldUploadedTokens[(string) $fid] = $tokStr;
                    $oldUploadedLabels[(string) $fid] = implode(', ', $labels);
                }
            }
        }

        return view('panel.data-submission-edit', compact(
            'submission',
            'form',
            'fields',
            'values',
            'rawValues',
            'issues',
            'attachmentMeta',
            'oldUploadedTokens',
            'oldUploadedLabels'
        ));
    }

    /**
     * Extract non-empty parameter across form input, raw JSON payload, query string, or HTTP header.
     *
     * @param  array<string, mixed>  $json
     */
    private function extractUploadParam(Request $request, array $json, string $key, ?string $header = null): string
    {
        $candidates = [
            $request->input($key),
            $json[$key] ?? null,
            $request->query($key),
            $header !== null ? $request->header($header) : null,
        ];

        foreach ($candidates as $val) {
            if ($val === null || is_array($val) || is_object($val)) {
                continue;
            }
            $str = trim((string) $val);
            if ($str !== '') {
                return $str;
            }
        }

        return '';
    }

    public function uploadSubmissionChunk(
        Request $request,
        FormSubmission $submission,
        DataQualityService $quality
    ): JsonResponse {
        $user = $request->user();
        $submission->loadMissing('dataForm.kolomFormulir');
        $form = $submission->dataForm;

        if (! $form) {
            return response()->json(['message' => 'Formulir tidak ditemukan.'], 404);
        }

        $rawContent = (string) $request->getContent();
        $jsonDecoded = json_decode($rawContent, true);
        $jsonPayload = is_array($jsonDecoded) ? $jsonDecoded : [];

        $fid = $this->extractUploadParam($request, $jsonPayload, 'field_id', 'X-Field-Id');
        $rawUploadId = $this->extractUploadParam($request, $jsonPayload, 'upload_id', 'X-Upload-Id');
        $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', $rawUploadId) ?: Str::random(16);

        $chunkIdxRaw = $this->extractUploadParam($request, $jsonPayload, 'chunk_index', 'X-Chunk-Index');
        $totalChunksRaw = $this->extractUploadParam($request, $jsonPayload, 'total_chunks', 'X-Total-Chunks');
        $chunkIndex = $chunkIdxRaw !== '' ? max(0, (int) $chunkIdxRaw) : 0;
        $totalChunks = $totalChunksRaw !== '' ? max(1, min(131072, (int) $totalChunksRaw)) : 1;

        $rawFileName = $this->extractUploadParam($request, $jsonPayload, 'file_name', null);
        $origName = basename($rawFileName !== ''
            ? $rawFileName
            : rawurldecode((string) $request->header('X-File-Name', 'berkas.bin')));

        $field = $fid !== '' ? collect($form->fields ?? [])->firstWhere('id', $fid) : null;
        if (! is_array($field) || ($field['type'] ?? '') !== 'file') {
            return response()->json([
                'message' => 'Kolom unggahan tidak cocok dengan versi formulir terbaru (formulir kemungkinan baru saja diubah). Muat ulang halaman ini (F5) lalu unggah ulang berkas Anda.',
                'reload_required' => true,
            ], 422);
        }

        $nameErr = $quality->fileNameError($field, $origName);
        if ($nameErr !== null) {
            return response()->json(['message' => $nameErr], 422);
        }

        $chunkB64 = $this->extractUploadParam($request, $jsonPayload, 'chunk_b64', null);
        if ($chunkB64 !== '') {
            $decoded = base64_decode($chunkB64, true);
            if ($decoded === false) {
                return response()->json(['message' => 'Format potongan berkas tidak valid.'], 422);
            }
            $bytes = $decoded;
        } elseif (($chunkFile = $request->file('chunk')) !== null) {
            if (! $chunkFile->isValid()) {
                return response()->json(['message' => 'Potongan berkas gagal diterima server (kode PHP '.$chunkFile->getError().').'], 422);
            }
            $bytes = file_get_contents($chunkFile->getRealPath());
            $bytes = $bytes === false ? '' : $bytes;
        } else {
            $bytes = $rawContent;
        }

        $config = is_array($field['config'] ?? null) ? $field['config'] : [];
        $rawMaxMb = array_key_exists('file_max_mb', $config) ? (int) $config['file_max_mb'] : 0;
        $maxMb = $rawMaxMb <= 0 ? 0 : min(1024, $rawMaxMb);

        $chunkRel = "chunks/fix_{$submission->code}_{$user->id}_{$uploadId}.part";
        $chunkAbs = Storage::disk('local')->path($chunkRel);
        $chunkDir = dirname($chunkAbs);
        if (! is_dir($chunkDir)) {
            mkdir($chunkDir, 0775, true);
        }

        if ($bytes === '') {
            if ($chunkIndex === 0 && is_file($chunkAbs)) {
                @unlink($chunkAbs);
            }

            return response()->json(['message' => 'Potongan berkas kosong (0 byte) atau gagal diterima server. Coba unggah ulang.'], 422);
        }

        $mode = $chunkIndex === 0 ? 'wb' : 'ab';
        $fp = fopen($chunkAbs, $mode);
        if ($fp === false) {
            return response()->json(['message' => 'Gagal menyimpan potongan berkas sementara.'], 500);
        }
        fwrite($fp, $bytes);
        fclose($fp);

        clearstatcache(true, $chunkAbs);
        $currentSize = (int) filesize($chunkAbs);

        if ($maxMb > 0 && $currentSize > $maxMb * 1024 * 1024) {
            @unlink($chunkAbs);

            return response()->json(['message' => "Ukuran berkas melebihi batas maksimal ({$maxMb} MB)."], 422);
        }

        if ($chunkIndex < $totalChunks - 1) {
            return response()->json([
                'ok' => true,
                'completed' => false,
                'chunk' => $chunkIndex,
            ]);
        }

        if ($currentSize <= 0) {
            @unlink($chunkAbs);

            return response()->json(['message' => 'Berkas kosong (0 byte) tidak dapat disimpan.'], 422);
        }

        $ext = strtolower((string) pathinfo($origName, PATHINFO_EXTENSION));
        $safeBase = Str::slug(pathinfo($origName, PATHINFO_FILENAME)) ?: 'berkas';
        $finalRel = "submissions/{$form->code}/".Str::random(24).'_'.$safeBase.($ext !== '' ? '.'.$ext : '');
        $finalAbs = Storage::disk('local')->path($finalRel);
        $finalDir = dirname($finalAbs);
        if (! is_dir($finalDir)) {
            mkdir($finalDir, 0775, true);
        }
        rename($chunkAbs, $finalAbs);

        if (! Storage::disk('local')->exists($finalRel) || (int) Storage::disk('local')->size($finalRel) <= 0) {
            return response()->json(['message' => 'Berkas gagal disimpan ke penyimpanan server.'], 500);
        }

        $token = Crypt::encryptString(json_encode([
            'scope' => 'preprocessing_edit',
            'submission_id' => (int) $submission->id,
            'user_id' => (int) $user->id,
            'form_id' => (int) $form->id,
            'field_id' => $fid,
            'path' => $finalRel,
            'name' => $origName,
            'size' => $currentSize,
        ], JSON_THROW_ON_ERROR));

        return response()->json([
            'ok' => true,
            'completed' => true,
            'token' => $token,
            'filename' => $origName,
            'size' => $currentSize,
        ], 201);
    }

    public function updateSubmission(
        Request $request,
        FormSubmission $submission,
        DataQualityService $quality,
        DataPreprocessingService $preprocessing
    ): RedirectResponse {
        $submission->loadMissing(['dataForm.kolomFormulir', 'dataIsian.kolomFormulir']);
        $form = $submission->dataForm;
        $form->loadMissing('kolomFormulir');
        $fields = $form->fields ?? [];
        $existingAttachments = $submission->attachments ?? [];
        $existingValues = $submission->values ?? [];

        $newValues = [];
        $attachmentsToSync = [];
        $oldFilesToDelete = [];
        $errors = [];
        $user = $request->user();

        foreach ($fields as $field) {
            $fid = (string) ($field['id'] ?? '');
            $type = (string) ($field['type'] ?? 'text');
            $required = (bool) ($field['required'] ?? false);
            $config = is_array($field['config'] ?? null) ? $field['config'] : [];

            if ($type === 'file') {
                $tokenRaw = trim((string) $request->input("uploaded_tokens.{$fid}", ''));
                $uploaded = $request->file("files.{$fid}");
                $fileList = is_array($uploaded) ? array_values(array_filter($uploaded)) : ($uploaded ? [$uploaded] : []);
                $removeRequested = $request->boolean("remove_files.{$fid}");
                $existingPath = $existingAttachments[$fid] ?? null;
                $hasValidExisting = is_string($existingPath)
                    && $existingPath !== ''
                    && Storage::disk('local')->exists($existingPath)
                    && (int) Storage::disk('local')->size($existingPath) > 0;
                $maxFiles = max(1, min(20, (int) ($config['file_max_count'] ?? 1)));
                $rawMaxMb = array_key_exists('file_max_mb', $config) ? (int) $config['file_max_mb'] : 0;
                $maxMb = $rawMaxMb <= 0 ? 0 : min(1024, $rawMaxMb);
                $allowedExts = $quality->allowedFileExtensions($field);

                if ($tokenRaw !== '' && $tokenRaw !== '[]' && empty($fileList)) {
                    $decodedTokens = json_decode($tokenRaw, true);
                    $tokenList = is_array($decodedTokens) ? $decodedTokens : [$tokenRaw];
                    $tokenList = array_values(array_filter(array_map('strval', $tokenList)));

                    if (empty($tokenList)) {
                        $errors[$fid] = 'Sesi berkas unggahan tidak valid. Unggah ulang berkas Anda.';
                        continue;
                    }

                    if (count($tokenList) > $maxFiles) {
                        $errors[$fid] = "Jumlah berkas maksimal {$maxFiles} file.";
                        continue;
                    }

                    $storedPaths = [];
                    $origNames = [];
                    $tokenFailed = false;

                    foreach ($tokenList as $tok) {
                        try {
                            $meta = json_decode(Crypt::decryptString($tok), true);
                        } catch (Throwable) {
                            $meta = null;
                        }

                        $tokPath = is_array($meta) ? (string) ($meta['path'] ?? '') : '';
                        $tokName = is_array($meta) ? (string) ($meta['name'] ?? '') : '';
                        $tokSize = is_array($meta) ? (int) ($meta['size'] ?? 0) : 0;

                        if (
                            ! is_array($meta)
                            || (int) ($meta['user_id'] ?? 0) !== (int) $user->id
                            || (int) ($meta['form_id'] ?? 0) !== (int) $form->id
                            || (string) ($meta['field_id'] ?? '') !== $fid
                            || (isset($meta['submission_id']) && (int) $meta['submission_id'] !== (int) $submission->id)
                            || $tokPath === ''
                            || ! Storage::disk('local')->exists($tokPath)
                        ) {
                            $errors[$fid] = 'Sesi berkas unggahan tidak valid atau kedaluwarsa. Unggah ulang berkas Anda.';
                            $tokenFailed = true;
                            break;
                        }

                        $actualSize = (int) Storage::disk('local')->size($tokPath);
                        if ($tokSize <= 0 || $actualSize <= 0) {
                            $errors[$fid] = 'Berkas unggahan kosong (0 byte) atau rusak. Unggah ulang berkas Anda.';
                            $tokenFailed = true;
                            break;
                        }

                        $nameErr = $quality->fileNameError($field, $tokName);
                        if ($nameErr !== null) {
                            $errors[$fid] = $nameErr;
                            $tokenFailed = true;
                            break;
                        }

                        if ($maxMb > 0 && $actualSize > $maxMb * 1024 * 1024) {
                            $errors[$fid] = "Ukuran berkas maksimal {$maxMb} MB.";
                            $tokenFailed = true;
                            break;
                        }

                        $storedPaths[] = $tokPath;
                        $origNames[] = $tokName;
                    }

                    if ($tokenFailed) {
                        continue;
                    }

                    $newValues[$fid] = implode(', ', $origNames);
                    $attachmentsToSync[$fid] = $storedPaths[0];
                    if (is_string($existingPath) && $existingPath !== '' && $existingPath !== $storedPaths[0]) {
                        $oldFilesToDelete[] = $existingPath;
                    }
                    continue;
                }

                if (! empty($fileList)) {
                    if (count($fileList) > $maxFiles) {
                        $errors[$fid] = "Jumlah berkas maksimal {$maxFiles} file.";
                        continue;
                    }

                    $storedPaths = [];
                    $origNames = [];
                    $fileInvalid = false;

                    foreach ($fileList as $uploadedFile) {
                        if (! $uploadedFile->isValid()) {
                            $errCode = $uploadedFile->getError();
                            $errors[$fid] = in_array($errCode, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                                ? 'Ukuran berkas melebihi batas unggahan langsung PHP ('.ini_get('upload_max_filesize').'). Gunakan pemilih berkas agar diunggah bertahap.'
                                : 'Berkas gagal diunggah (kode PHP '.$errCode.').';
                            $fileInvalid = true;
                            break;
                        }

                        if ((int) $uploadedFile->getSize() <= 0) {
                            $errors[$fid] = 'Berkas kosong (0 byte) tidak dapat diunggah.';
                            $fileInvalid = true;
                            break;
                        }

                        $origName = $uploadedFile->getClientOriginalName();
                        $nameErr = $quality->fileNameError($field, $origName);
                        if ($nameErr !== null) {
                            $errors[$fid] = $nameErr;
                            $fileInvalid = true;
                            break;
                        }

                        $fileRules = ['file'];
                        $customMessages = [];
                        if ($allowedExts !== null) {
                            $allowedUpper = strtoupper(implode(', ', $allowedExts));
                            $fileRules[] = 'mimes:'.implode(',', $allowedExts);
                            $customMessages['file.mimes'] = "Format berkas harus berupa: {$allowedUpper}.";
                        }
                        if ($maxMb > 0) {
                            $fileRules[] = 'max:'.($maxMb * 1024);
                            $customMessages['file.max'] = "Ukuran berkas maksimal {$maxMb} MB.";
                        }

                        $fileValidator = Validator::make(['file' => $uploadedFile], ['file' => $fileRules], $customMessages);
                        if ($fileValidator->fails()) {
                            $errors[$fid] = $fileValidator->errors()->first('file');
                            $fileInvalid = true;
                            break;
                        }

                        $storedPath = $uploadedFile->store("submissions/{$form->code}", 'local');
                        if ($storedPath !== false) {
                            $storedPaths[] = $storedPath;
                            $origNames[] = $origName;
                        }
                    }

                    if ($fileInvalid) {
                        continue;
                    }

                    $newValues[$fid] = implode(', ', $origNames);
                    $attachmentsToSync[$fid] = $storedPaths[0];
                    if (is_string($existingPath) && $existingPath !== '' && $existingPath !== $storedPaths[0]) {
                        $oldFilesToDelete[] = $existingPath;
                    }
                    continue;
                }

                if ($removeRequested) {
                    if ($required) {
                        $errors[$fid] = 'Lampiran bukti wajib diunggah.';
                    } else {
                        $newValues[$fid] = '';
                        $attachmentsToSync[$fid] = '';
                        if (is_string($existingPath) && $existingPath !== '') {
                            $oldFilesToDelete[] = $existingPath;
                        }
                    }
                    continue;
                }

                $submittedText = trim((string) $request->input("values.{$fid}", ''));
                if ($hasValidExisting) {
                    $newValues[$fid] = $submittedText !== '' ? $submittedText : ((string) ($existingValues[$fid] ?? '') ?: basename($existingPath));
                    $attachmentsToSync[$fid] = $existingPath;
                } elseif ($submittedText !== '' && $quality->fieldError($field, $submittedText) === null) {
                    $newValues[$fid] = $submittedText;
                } elseif ($required) {
                    $errors[$fid] = 'Lampiran bukti wajib diunggah.';
                } else {
                    $newValues[$fid] = '';
                    if ($existingPath) {
                        $attachmentsToSync[$fid] = '';
                        $oldFilesToDelete[] = $existingPath;
                    }
                }
                continue;
            }

            $rawInput = $request->input("values.{$fid}", '');
            $otherInput = trim((string) $request->input("values_other.{$fid}", ''));

            if ($type === 'radio') {
                $val = is_array($rawInput) ? (string) ($rawInput[0] ?? '') : trim((string) $rawInput);
                if ($val === '__other__') {
                    $val = $otherInput;
                }
            } elseif ($type === 'checkbox') {
                if (is_array($rawInput)) {
                    $picked = [];
                    foreach ($rawInput as $item) {
                        $itemStr = trim((string) $item);
                        if ($itemStr === '__other__') {
                            if ($otherInput !== '') {
                                $picked[] = $otherInput;
                            }
                        } elseif ($itemStr !== '') {
                            $picked[] = $itemStr;
                        }
                    }
                    $val = implode(', ', $picked);
                } else {
                    $str = trim((string) $rawInput);
                    $val = $str === '__other__' ? $otherInput : $str;
                }
            } elseif ($type === 'mc_grid' || $type === 'checkbox_grid') {
                if (is_array($rawInput)) {
                    $gridRows = array_values(array_filter(array_map('strval', $config['grid_rows'] ?? [])));
                    $segments = [];
                    foreach ($gridRows as $rIdx => $rowLabel) {
                        $cellVal = $rawInput[$rIdx] ?? ($rawInput[$rowLabel] ?? null);
                        if (is_array($cellVal)) {
                            $cols = array_values(array_filter(array_map(
                                static fn ($c): string => trim((string) $c),
                                $cellVal
                            ), static fn (string $c): bool => $c !== ''));
                            if (! empty($cols)) {
                                $segments[] = $rowLabel.': '.implode(', ', $cols);
                            }
                        } elseif (is_string($cellVal) && trim($cellVal) !== '') {
                            $segments[] = $rowLabel.': '.trim($cellVal);
                        }
                    }
                    $val = implode('; ', $segments);
                } else {
                    $val = trim((string) $rawInput);
                }
            } elseif ($type === 'textarea') {
                $val = is_array($rawInput) ? implode("\n", $rawInput) : (string) $rawInput;
            } else {
                $val = is_array($rawInput) ? implode(', ', $rawInput) : trim((string) $rawInput);
            }

            $err = $quality->fieldError($field, $val);
            if ($err !== null) {
                $errors[$fid] = $err;
            } else {
                $newValues[$fid] = $val;
            }
        }

        if (! empty($errors)) {
            return back()
                ->withInput()
                ->withErrors($errors);
        }

        DB::transaction(function () use ($submission, $form, $newValues, $attachmentsToSync, $request, $preprocessing): void {
            $submission->syncValues(
                $form,
                $newValues,
                ! empty($attachmentsToSync) ? $attachmentsToSync : null,
                null,
                true
            );
            $submission->update([
                'status_validasi' => 'mentah',
                'catatan_validasi' => null,
                'dibersihkan_oleh' => $request->user()->id,
                'waktu_dibersihkan' => now(),
            ]);
            $preprocessing->markFormNeedsPreprocessing($form);
        });

        foreach ($oldFilesToDelete as $oldPath) {
            if (is_string($oldPath) && $oldPath !== '' && Storage::disk('local')->exists($oldPath)) {
                Storage::disk('local')->delete($oldPath);
            }
        }

        return redirect()
            ->route('panel.preprocessing', ['form' => $form->code])
            ->with('success', "Perbaikan data isian {$submission->code} disimpan. Jalankan Finalisasi Pre-processing setelah seluruh data siap.");
    }

    public function updateSubmissionStatus(
        Request $request,
        FormSubmission $submission,
        DataPreprocessingService $preprocessing
    ): RedirectResponse {
        $validated = $request->validate([
            'status_validasi' => ['required', 'in:mentah,ditolak'],
            'catatan_validasi' => ['nullable', 'string', 'max:500'],
        ]);

        $form = $submission->dataForm;
        $submission->update([
            'status_validasi' => $validated['status_validasi'],
            'catatan_validasi' => trim((string) ($validated['catatan_validasi'] ?? '')) ?: null,
            'dibersihkan_oleh' => $request->user()->id,
            'waktu_dibersihkan' => now(),
        ]);

        if ($form) {
            $preprocessing->markFormNeedsPreprocessing($form);
        }

        $msg = $validated['status_validasi'] === 'ditolak'
            ? "Isian {$submission->code} ditandai ditolak dan dikecualikan dari penyusunan dataset."
            : "Isian {$submission->code} dipulihkan ke antrean pre-processing.";

        return back()->with('success', $msg);
    }

    public function destroySubmission(
        FormSubmission $submission,
        DataPreprocessingService $preprocessing
    ): RedirectResponse {
        $form = $submission->dataForm;
        $submission->loadMissing('dataIsian.kolomFormulir');

        foreach ($submission->attachments ?? [] as $path) {
            if (is_string($path) && $path !== '' && Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        }

        $submission->delete();

        if ($form) {
            $preprocessing->markFormNeedsPreprocessing($form);
        }

        return back()->with('success', 'Isian dihapus.');
    }
}
