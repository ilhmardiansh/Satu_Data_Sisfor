<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DashboardMetric;
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

        $visualizations = Visualization::query()
            ->with(['dataset.category'])
            ->where('show', true)
            ->orderBy('id')
            ->get()
            ->filter(function (Visualization $viz) use ($privileged): bool {
                $dataset = $viz->dataset;
                if (! $dataset) {
                    return false;
                }

                return $privileged || $dataset->access === 'public';
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

            $cols = $dataset->cols ?? [];
            $rows = $dataset->rows ?? [];
            $numericMap = ChartPaletteService::numericColumns($cols, $rows);
            unset($numericMap[$viz->label_col]);

            $years[] = $dataset->tahun;
            if ($dataset->category) {
                $usedCategoryCodes[] = $dataset->category->code;
            }

            $vizList[] = [
                'id' => $viz->code,
                'title' => $viz->title,
                'datasetId' => $dataset->code,
                'datasetTitle' => $dataset->title,
                'tahun' => $dataset->tahun,
                'kategori' => $dataset->category?->code ?? '',
                'kategoriName' => $dataset->category?->name ?? '',
                'labelCol' => $viz->label_col,
                'valueCol' => $viz->value_col,
                'kind' => $viz->kind,
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
            ->get(['code', 'name'])
            ->toArray();

        $selectedYear = (string) $request->query('tahun', 'all');
        $selectedCat = (string) $request->query('kategori', 'all');

        $groupedMetrics = DashboardMetric::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('section');

        $formatMetric = static function (DashboardMetric $metric): array {
            return [
                'id' => $metric->id,
                'title' => $metric->title,
                'color' => $metric->color,
                'palette' => ChartPaletteService::palette($metric->color),
                'total' => $metric->total,
                'unit' => $metric->unit,
                'def' => $metric->default_kind,
                'trend' => $metric->trend_value ? [
                    'v' => $metric->trend_value,
                    'up' => (bool) $metric->trend_up,
                ] : null,
                'data' => $metric->data ?? [],
                'foot' => $metric->foot,
                'table' => $metric->table_data ?? [],
            ];
        };

        $metrics = [
            'akademik1' => $groupedMetrics->get('akademik1', collect())->map($formatMetric)->values()->all(),
            'akademik2' => $groupedMetrics->get('akademik2', collect())->map($formatMetric)->values()->all(),
            'nonakademik1' => $groupedMetrics->get('nonakademik1', collect())->map($formatMetric)->values()->all(),
            'nonakademik2' => $groupedMetrics->get('nonakademik2', collect())->map($formatMetric)->values()->all(),
        ];

        return view('pages.dashboard', compact(
            'metrics',
            'vizList',
            'years',
            'cats',
            'selectedYear',
            'selectedCat'
        ));
    }
}
