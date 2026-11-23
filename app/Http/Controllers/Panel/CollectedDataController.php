<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DataForm;
use App\Models\FormSubmission;
use App\Services\DataQualityService;
use App\Services\SpreadsheetExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CollectedDataController extends Controller
{
    public function index(Request $request, DataQualityService $quality): View
    {
        $kategori = (string) $request->query('kategori', 'all');
        $tahun = (string) $request->query('tahun', 'all');
        $formCode = (string) $request->query('form', '');
        $q = trim((string) $request->query('q', ''));
        $onlyIssues = $request->boolean('only_issues');

        $allForms = DataForm::query()
            ->with('category')
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
        $usedCategoryIds = $allForms->pluck('category_id')->unique()->all();
        $filterCategories = $categories->whereIn('id', $usedCategoryIds)->values();
        $years = $allForms->pluck('tahun')->unique()->sortDesc()->values();

        $qualityIssues = [];
        $problemCount = 0;
        $totalSubmissionsCount = 0;
        $submissionsPaginator = new LengthAwarePaginator([], 0, 10);

        if ($activeForm) {
            $allSubs = $activeForm->submissions()
                ->with(['user', 'cleaner'])
                ->orderByDesc('submitted_at')
                ->get();

            $totalSubmissionsCount = $allSubs->count();
            $qualityIssues = $quality->inspectSubmissions($activeForm, $allSubs);
            $problemCount = count(array_filter(
                $qualityIssues,
                fn (array $issues): bool => ! empty($issues)
            ));

            $kw = strtolower($q);
            $filteredSubs = $allSubs->filter(function (FormSubmission $sub) use ($activeForm, $qualityIssues, $onlyIssues, $kw): bool {
                if ($onlyIssues && empty($qualityIssues[$sub->id])) {
                    return false;
                }

                if ($kw !== '') {
                    $cells = [
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
            $perPage = 10;
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

        // Builder payload for DatasetBuilder modal
        $builderFormsPayload = $allForms->map(function (DataForm $f) use ($quality): array {
            $subs = $f->submissions()->with('user')->orderByDesc('submitted_at')->get();
            $issuesMap = $quality->inspectSubmissions($f, $subs);
            $dirty = collect($issuesMap)->contains(fn (array $issues): bool => ! empty($issues));

            return [
                'id' => $f->id,
                'code' => $f->code,
                'title' => $f->title,
                'desc' => $f->description ?? '',
                'category_id' => $f->category_id,
                'kategoriCode' => $f->category?->code ?? '',
                'tahun' => $f->tahun,
                'periode' => $f->periode,
                'fields' => $f->fields ?? [],
                'sourceCols' => $f->sourceCols(),
                'dirty' => $dirty,
                'subs' => $subs->map(fn (FormSubmission $s): array => [
                    'nama' => $s->user?->name ?? '-',
                    'nim' => $s->user?->nim ?? '-',
                    'at' => $s->submittedFormatted(),
                    'values' => $s->values ?? [],
                ])->values()->all(),
            ];
        })->values()->all();

        return view('panel.data', compact(
            'categories',
            'filterCategories',
            'years',
            'filteredForms',
            'activeForm',
            'submissionsPaginator',
            'qualityIssues',
            'problemCount',
            'totalSubmissionsCount',
            'kategori',
            'tahun',
            'q',
            'onlyIssues',
            'builderFormsPayload'
        ));
    }

    public function exportCsv(DataForm $dataForm, SpreadsheetExportService $exporter): StreamedResponse
    {
        $subs = $dataForm->submissions()
            ->with('user')
            ->orderByDesc('submitted_at')
            ->get();

        $sourceCols = $dataForm->sourceCols();
        $cols = array_map(fn (array $c): string => $c['label'], $sourceCols);
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
            $rows[] = $row;
        }

        return $exporter->downloadCsv($dataForm->title, $cols, $rows);
    }

    public function updateSubmission(
        Request $request,
        FormSubmission $submission,
        DataQualityService $quality
    ): RedirectResponse {
        $form = $submission->dataForm;
        $fields = $form->fields ?? [];
        $newValues = [];
        $errors = [];

        foreach ($fields as $field) {
            $fid = (string) ($field['id'] ?? '');
            $val = (string) $request->input("values.{$fid}", '');
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

        $submission->update([
            'values' => $newValues,
            'cleaned_by' => $request->user()->id,
            'cleaned_at' => now(),
        ]);

        return back()->with('success', 'Perbaikan disimpan. Kualitas data diperiksa ulang.');
    }

    public function destroySubmission(FormSubmission $submission): RedirectResponse
    {
        foreach ($submission->attachments ?? [] as $path) {
            if (is_string($path) && $path !== '' && Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        }

        $submission->delete();

        return back()->with('success', 'Isian dihapus.');
    }
}
