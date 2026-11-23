<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Models\Visualization;
use App\Services\ChartPaletteService;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VisualizationController extends Controller
{
    public function index(): View
    {
        $visualizations = Visualization::query()
            ->with(['dataset', 'creator'])
            ->orderBy('id')
            ->get();

        $usableDatasets = Dataset::query()
            ->orderByDesc('tahun')
            ->orderBy('id')
            ->get()
            ->filter(fn (Dataset $d): bool => ! empty($d->numericColumns()))
            ->values();

        $datasetsPayload = $usableDatasets->map(function (Dataset $d): array {
            return [
                'id' => $d->id,
                'code' => $d->code,
                'title' => $d->title,
                'access' => $d->access,
                'cols' => $d->cols ?? [],
                'rows' => $d->rows ?? [],
                'numericCols' => $d->numericColumns(),
            ];
        })->values()->all();

        $vizPayload = $visualizations->map(function (Visualization $v): array {
            return [
                'id' => $v->id,
                'code' => $v->code,
                'title' => $v->title,
                'dataset_id' => $v->dataset_id,
                'datasetTitle' => $v->dataset?->title ?? 'Dataset dihapus',
                'creatorName' => $v->creator?->name ?? 'Pengelola',
                'created_by' => $v->created_by,
                'label_col' => $v->label_col,
                'value_col' => $v->value_col,
                'kind' => $v->kind,
                'color' => $v->color,
                'show' => $v->show,
                'cols' => $v->dataset?->cols ?? [],
                'rows' => $v->dataset?->rows ?? [],
                'palette' => ChartPaletteService::palette($v->color),
            ];
        })->values()->all();

        $palettes = ChartPaletteService::PALETTES;
        $kinds = ChartPaletteService::KINDS;

        return view('panel.visualisasi', compact(
            'visualizations',
            'usableDatasets',
            'datasetsPayload',
            'vizPayload',
            'palettes',
            'kinds'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);

        $viz = Visualization::create([
            'code' => CodeGenerator::next('V', 'visualizations'),
            'title' => $data['title'],
            'dataset_id' => $data['dataset_id'],
            'label_col' => $data['label_col'],
            'value_col' => $data['value_col'],
            'kind' => $data['kind'],
            'color' => $data['color'],
            'show' => $data['show'],
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('panel.visualisasi')
            ->with('success', $viz->show
                ? 'Visualisasi disimpan dan tampil di Dashboard.'
                : 'Visualisasi disimpan (tidak ditampilkan).');
    }

    public function update(Request $request, Visualization $visualization): RedirectResponse
    {
        $data = $this->validatePayload($request);

        $visualization->update([
            'title' => $data['title'],
            'dataset_id' => $data['dataset_id'],
            'label_col' => $data['label_col'],
            'value_col' => $data['value_col'],
            'kind' => $data['kind'],
            'color' => $data['color'],
            'show' => $data['show'],
        ]);

        return redirect()->route('panel.visualisasi')
            ->with('success', $visualization->show
                ? 'Visualisasi disimpan dan tampil di Dashboard.'
                : 'Visualisasi disimpan (tidak ditampilkan).');
    }

    public function toggle(Request $request, Visualization $visualization): JsonResponse|RedirectResponse
    {
        $newShow = $request->has('show') ? $request->boolean('show') : ! $visualization->show;
        $visualization->update(['show' => $newShow]);

        if ($request->expectsJson()) {
            return response()->json([
                'show' => $newShow,
                'message' => $newShow ? 'Tampil di Dashboard' : 'Disembunyikan',
            ]);
        }

        return back()->with('info', $newShow ? 'Visualisasi ditampilkan di Dashboard.' : 'Visualisasi disembunyikan.');
    }

    public function destroy(Request $request, Visualization $visualization): RedirectResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && $visualization->created_by !== $user->id) {
            abort(403, 'Anda tidak berhak menghapus visualisasi ini.');
        }

        $visualization->delete();

        return redirect()->route('panel.visualisasi')
            ->with('success', 'Visualisasi dihapus.');
    }

    /**
     * @return array{
     *     title: string,
     *     dataset_id: int,
     *     label_col: int,
     *     value_col: int,
     *     kind: string,
     *     color: string,
     *     show: bool
     * }
     */
    protected function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'dataset_id' => ['required', 'integer', 'exists:datasets,id'],
            'label_col' => ['required', 'integer', 'min:0'],
            'value_col' => ['required', 'integer', 'min:0'],
            'kind' => ['required', Rule::in(array_keys(ChartPaletteService::KINDS))],
            'color' => ['required', Rule::in(array_keys(ChartPaletteService::PALETTES))],
            'show' => ['nullable', 'boolean'],
        ], [
            'title.required' => 'Judul wajib diisi.',
        ]);

        $dataset = Dataset::query()->findOrFail($validated['dataset_id']);
        $numericCols = $dataset->numericColumns();
        $valueCol = (int) $validated['value_col'];

        if (! array_key_exists($valueCol, $numericCols)) {
            return back()->withErrors([
                'value_col' => 'Pilih kolom nilai angka yang valid untuk visualisasi.',
            ])->throwResponse();
        }

        return [
            'title' => trim($validated['title']),
            'dataset_id' => (int) $validated['dataset_id'],
            'label_col' => (int) $validated['label_col'],
            'value_col' => $valueCol,
            'kind' => $validated['kind'],
            'color' => $validated['color'],
            'show' => $request->boolean('show'),
        ];
    }
}
