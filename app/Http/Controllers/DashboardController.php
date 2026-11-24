<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Dataset;
use App\Models\Visualization;
use App\Services\ChartPaletteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $privileged = $user && $user->isStaff();

        $visualizations = Visualization::datasetCharts()
            ->with(['dataset.category'])
            ->orderBy('id')
            ->get()
            ->filter(function (Visualization $viz) use ($privileged): bool {
                $dataset = $viz->dataset;
                if (! $dataset || ! $viz->show || ! $dataset->isPublished()) {
                    return false;
                }

                return $privileged || $dataset->tipe_akses === 'public';
            })
            ->values();

        $vizList = [];
        $years = [];
        $usedCategoryCodes = [];

        foreach ($visualizations as $viz) {
            $dataset = $viz->dataset;
            if (! $dataset) {
                continue;
            }

            $cols = $dataset->kolom ?? [];
            $rows = $dataset->baris ?? [];
            $numericMap = ChartPaletteService::numericColumns($cols, $rows);
            unset($numericMap[$viz->label_col]);

            $years[] = $dataset->tahun;
            if ($dataset->category) {
                $usedCategoryCodes[] = $dataset->category->code;
            }

            $vizList[] = [
                'id' => $viz->code,
                'title' => $viz->judul,
                'datasetId' => $dataset->code,
                'datasetTitle' => $dataset->judul,
                'datasetUrl' => route('dataset', ['q' => $dataset->judul]),
                'tahun' => $dataset->tahun,
                'kategori' => $dataset->category?->code ?? '',
                'kategoriName' => $dataset->category?->nama_kategori ?? '',
                'labelCol' => $viz->label_col,
                'valueCol' => $viz->value_col,
                'kind' => $viz->tipe_grafik,
                'color' => $viz->color,
                'palette' => ChartPaletteService::palette($viz->color),
                'cols' => $cols,
                'rows' => $rows,
                'numericCols' => $numericMap,
            ];
        }

        $years = array_values(array_unique($years));
        rsort($years);

        $usedCategoryCodes = array_values(array_unique($usedCategoryCodes));
        $cats = Category::query()
            ->whereIn('code', $usedCategoryCodes)
            ->orderBy('id')
            ->get()
            ->map(fn (Category $cat): array => [
                'code' => $cat->code,
                'name' => $cat->nama_kategori,
            ])
            ->values()
            ->all();

        $selectedYear = (string) $request->query('tahun', 'all');
        $selectedCat = (string) $request->query('kategori', 'all');

        $groupedMetrics = Visualization::metricCards()
            ->orderBy('id')
            ->get()
            ->sortBy(fn (Visualization $m): int => (int) ($m->konfigurasi_query['sort_order'] ?? 0))
            ->groupBy(fn (Visualization $m): string => (string) ($m->konfigurasi_query['section'] ?? 'akademik1'));

        $formatMetric = static function (Visualization $metric): array {
            $cfg = $metric->konfigurasi_query ?? [];
            $color = (string) ($cfg['color'] ?? 'sky');
            $trendVal = $cfg['trend_value'] ?? null;

            return [
                'id' => $metric->id,
                'title' => $metric->judul,
                'color' => $color,
                'palette' => ChartPaletteService::palette($color),
                'total' => (string) ($cfg['total'] ?? '0'),
                'unit' => (string) ($cfg['unit'] ?? ''),
                'def' => $metric->tipe_grafik,
                'trend' => $trendVal ? [
                    'v' => (string) $trendVal,
                    'up' => (bool) ($cfg['trend_up'] ?? true),
                ] : null,
                'data' => $cfg['data'] ?? [],
                'foot' => $cfg['foot'] ?? null,
                'table' => $cfg['table_data'] ?? [],
            ];
        };

        $metrics = [
            'akademik1' => $groupedMetrics->get('akademik1', collect())->map($formatMetric)->values()->all(),
            'akademik2' => $groupedMetrics->get('akademik2', collect())->map($formatMetric)->values()->all(),
            'nonakademik1' => $groupedMetrics->get('nonakademik1', collect())->map($formatMetric)->values()->all(),
            'nonakademik2' => $groupedMetrics->get('nonakademik2', collect())->map($formatMetric)->values()->all(),
        ];

        $summaryStats = [
            'metricCards' => count($metrics['akademik1']) + count($metrics['akademik2']) + count($metrics['nonakademik1']) + count($metrics['nonakademik2']),
            'featuredCharts' => count($vizList),
            'publicDatasets' => Dataset::published()->where('tipe_akses', 'public')->count(),
            'categories' => Category::query()->count(),
        ];

        return view('pages.dashboard', compact(
            'metrics',
            'vizList',
            'years',
            'cats',
            'selectedYear',
            'selectedCat',
            'summaryStats'
        ));
    }
}
