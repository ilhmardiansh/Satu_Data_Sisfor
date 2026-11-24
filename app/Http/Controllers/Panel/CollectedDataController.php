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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        $kategori = (string) $request->query('kategori', 'all');
        $tahun = (string) $request->query('tahun', 'all');
        $formCode = (string) $request->query('form', '');
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', $request->boolean('only_issues') ? 'issues' : 'all');
        if (! in_array($status, ['all', 'mentah', 'valid', 'ditolak', 'issues'], true)) {
            $status = 'all';
        }
        $onlyIssues = $status === 'issues';

        $allForms = DataForm::query()
            ->with(['category', 'kolomFormulir', 'processor'])
            ->withCount('submissions')
            ->orderByDesc('created_at')
            ->get();

        $filteredForms = $allForms->filter(function (DataForm $f) use ($kategori, $tahun): bool {
            $matchCat = $kategori === 'all' || $f->category?->code === $kategori;
            $matchYear = $tahun === 'all' || (string) $f->tahun === $tahun;

            return $matchCat && $matchYear;
        })->values();

        $activeForm = $filteredForms->firstWhere('code', $formCode)
            ?? $filteredForms->first();

        $categories = Category::query()->orderBy('id')->get();
        $usedCategoryIds = $allForms->pluck('id_kategori')->unique()->all();
        $filterCategories = $categories->whereIn('id', $usedCategoryIds)->values();
        $years = $allForms->pluck('tahun')->unique()->sortDesc()->values();

        $qualityIssues = [];
        $profile = [
            'total' => 0,
            'mentah' => 0,
            'valid' => 0,
            'ditolak' => 0,
            'active_count' => 0,
            'issue_rows' => 0,
            'duplicate_rows' => 0,
            'clean_active_rows' => 0,
            'columns' => [],
        ];
        $problemCount = 0;
        $totalSubmissionsCount = 0;
        $preprocessingLogs = [];
        $submissionsPaginator = new LengthAwarePaginator([], 0, 15);

        if ($activeForm) {
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
        }

        $builderFormsPayload = $builder->builderFormsPayload($allForms, $quality);

        return view('panel.data', compact(
            'categories',
            'filterCategories',
            'years',
            'filteredForms',
            'activeForm',
            'submissionsPaginator',
            'qualityIssues',
            'profile',
            'preprocessingLogs',
            'problemCount',
            'totalSubmissionsCount',
            'kategori',
            'tahun',
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
        $fields = $dataForm->kolomFormulir->values();

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

    public function updateSubmission(
        Request $request,
        FormSubmission $submission,
        DataQualityService $quality,
        DataPreprocessingService $preprocessing
    ): RedirectResponse {
        $form = $submission->dataForm;
        $form->loadMissing('kolomFormulir');
        $fields = $form->fields ?? [];
        $newValues = [];
        $errors = [];

        foreach ($fields as $field) {
            $fid = (string) ($field['id'] ?? '');
            $type = (string) ($field['type'] ?? 'text');
            $rawInput = $request->input("values.{$fid}", '');
            if ($type === 'checkbox' && is_array($rawInput)) {
                $val = implode(', ', array_values(array_filter(array_map('trim', array_map('strval', $rawInput)), static fn (string $x): bool => $x !== '')));
            } else {
                $val = is_array($rawInput) ? implode(', ', $rawInput) : (string) $rawInput;
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
                ->withErrors($errors)
                ->with('clean_submission_code', $submission->code);
        }

        DB::transaction(function () use ($submission, $form, $newValues, $request, $preprocessing): void {
            $submission->syncValues($form, $newValues, null, null, true);
            $submission->update([
                'status_validasi' => 'mentah',
                'catatan_validasi' => null,
                'dibersihkan_oleh' => $request->user()->id,
                'waktu_dibersihkan' => now(),
            ]);
            $preprocessing->markFormNeedsPreprocessing($form);
        });

        return back()->with('success', 'Perbaikan disimpan. Jalankan Finalisasi Pre-processing setelah seluruh data siap.');
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
