<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DataForm;
use App\Models\Dataset;
use App\Models\FormSubmission;
use App\Services\DataQualityService;
use App\Services\DatasetBuilderService;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManageDatasetController extends Controller
{
    public function index(Request $request, DataQualityService $quality): View
    {
        $q = trim((string) $request->query('q', ''));
        $kategori = (string) $request->query('kategori', 'all');
        $tahun = (string) $request->query('tahun', 'all');

        $query = Dataset::query()
            ->with('category')
            ->orderByDesc('tahun')
            ->orderByDesc('id');

        if ($kategori !== '' && $kategori !== 'all') {
            $query->whereHas('category', function ($sub) use ($kategori): void {
                $sub->where('code', $kategori);
            });
        }

        if ($tahun !== '' && $tahun !== 'all') {
            $query->where('tahun', (int) $tahun);
        }

        if ($q !== '') {
            $like = '%'.strtolower($q).'%';
            $query->whereRaw('LOWER(title) LIKE ?', [$like]);
        }

        $datasets = $query->paginate(10)->withQueryString();
        $categories = Category::query()->orderBy('id')->get();
        $years = Dataset::query()->distinct()->orderByDesc('tahun')->pluck('tahun');

        $allForms = DataForm::query()
            ->with('category')
            ->orderByDesc('created_at')
            ->get();

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

        return view('panel.dataset', compact(
            'datasets',
            'categories',
            'years',
            'q',
            'kategori',
            'tahun',
            'builderFormsPayload'
        ));
    }

    public function storeFromForm(
        Request $request,
        DataQualityService $quality,
        DatasetBuilderService $builder
    ): RedirectResponse {
        $validated = $request->validate([
            'form_id' => ['required', 'integer', 'exists:data_forms,id'],
            'mode' => ['required', 'in:rekap,rinci'],
            'group_field_id' => ['required_if:mode,rekap', 'nullable', 'string'],
            'picked_cols' => ['required_if:mode,rinci', 'array'],
            'picked_cols.*' => ['string'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'tahun' => ['required', 'digits:4'],
            'periode' => ['required', 'in:Ganjil,Genap'],
            'access' => ['required', 'in:public,private'],
            'format' => ['required', 'in:CSV,XLSX'],
        ], [
            'title.required' => 'Judul wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'tahun.digits' => 'Tahun 4 digit.',
            'picked_cols.required_if' => 'Pilih minimal satu kolom.',
        ]);

        $form = DataForm::query()->findOrFail($validated['form_id']);
        $subs = $form->submissions()->with('user')->orderByDesc('submitted_at')->get();

        if ($subs->isEmpty()) {
            return back()->withInput()->with('error', 'Belum ada isian sumber untuk dibuat menjadi dataset.');
        }

        $issuesMap = $quality->inspectSubmissions($form, $subs);
        $hasIssues = collect($issuesMap)->contains(fn (array $issues): bool => ! empty($issues));
        if ($hasIssues) {
            return back()->withInput()->with('error', 'Data sumber belum bersih. Perbaiki melalui menu Data Terkumpul sebelum membuat dataset.');
        }

        $built = $builder->buildFromForm(
            $form,
            $subs,
            $validated['mode'],
            $validated['group_field_id'] ?? null,
            $validated['picked_cols'] ?? []
        );

        if (empty($built['cols'])) {
            return back()->withInput()->with('error', 'Pilih minimal satu kolom.');
        }

        $dataset = Dataset::create([
            'code' => CodeGenerator::next('DS', 'datasets'),
            'category_id' => (int) $validated['category_id'],
            'source_form_id' => $form->id,
            'title' => trim($validated['title']),
            'description' => trim((string) ($validated['description'] ?? '')) ?: "Dibuat dari formulir {$form->title}.",
            'tahun' => (int) $validated['tahun'],
            'periode' => $validated['periode'],
            'access' => $validated['access'],
            'format' => $validated['format'],
            'updated_label' => now()->locale('id')->translatedFormat('j M Y'),
            'cols' => $built['cols'],
            'rows' => $built['rows'],
            'created_by' => $request->user()->id,
        ]);

        $accessLabel = $dataset->access === 'public' ? 'publik' : 'private';

        return redirect()->route('panel.dataset')
            ->with('success', "Dataset “{$dataset->title}” dibuat ({$accessLabel}).");
    }

    public function toggleAccess(Request $request, Dataset $dataset): JsonResponse|RedirectResponse
    {
        $newAccess = $request->has('access')
            ? ($request->input('access') === 'public' ? 'public' : 'private')
            : ($dataset->access === 'public' ? 'private' : 'public');

        $dataset->update([
            'access' => $newAccess,
        ]);

        $label = $newAccess === 'public' ? 'publik' : 'private';
        $message = "“{$dataset->title}” kini {$label}.";

        if ($request->expectsJson()) {
            return response()->json([
                'access' => $newAccess,
                'message' => $message,
            ]);
        }

        return back()->with('info', $message);
    }

    public function update(Request $request, Dataset $dataset): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'tahun' => ['required', 'digits:4'],
            'periode' => ['required', 'in:Ganjil,Genap'],
            'access' => ['required', 'in:public,private'],
            'format' => ['required', 'in:CSV,XLSX'],
        ], [
            'title.required' => 'Judul wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'tahun.digits' => 'Tahun 4 digit.',
        ]);

        $dataset->update([
            'title' => trim($validated['title']),
            'description' => trim((string) ($validated['description'] ?? '')),
            'category_id' => (int) $validated['category_id'],
            'tahun' => (int) $validated['tahun'],
            'periode' => $validated['periode'],
            'access' => $validated['access'],
            'format' => $validated['format'],
            'updated_label' => now()->locale('id')->translatedFormat('j M Y'),
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
