<?php

namespace App\Http\Controllers;

use App\Services\SatuDataService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $metrics = SatuDataService::getMetrics();
        $allDatasets = SatuDataService::getDatasets();
        $categories = SatuDataService::getCategories();

        $selectedYear = (string) $request->query('tahun', 'all');
        $selectedCat = (string) $request->query('kategori', 'all');

        // Palettes mapping matching src/charts.tsx
        $palettes = [
            'sky' => ['#4472d0', '#0ea5e9', '#a0bbea', '#0f172a', '#7dd3fc', '#94a3b8'],
            'blue' => ['#3760bb', '#7398dd', '#a0bbea', '#1c2a4a', '#c7d6f3', '#94a3b8'],
            'indigo' => ['#4f46e5', '#818cf8', '#a5b4fc', '#312e81', '#c7d2fe', '#94a3b8'],
            'cyan' => ['#0891b2', '#22d3ee', '#67e8f9', '#164e63', '#a5f3fc', '#94a3b8'],
            'teal' => ['#0d9488', '#2dd4bf', '#5eead4', '#134e4a', '#99f6e4', '#94a3b8'],
            'violet' => ['#7c3aed', '#a78bfa', '#c4b5fd', '#4c1d95', '#ddd6fe', '#94a3b8'],
        ];

        // Process visualizations and attach dataset details
        $vizList = [];
        $years = [];
        $usedCategoryCodes = [];

        foreach ($metrics['visualizations'] as $v) {
            $dataset = collect($allDatasets)->firstWhere('id', $v['datasetId']);
            if (!$dataset) continue;

            $years[] = $dataset['tahun'];
            $usedCategoryCodes[] = $dataset['kategori'];

            // Find numeric columns excluding labelCol
            $numericCols = [];
            foreach ($dataset['cols'] as $i => $colName) {
                if ($i === $v['labelCol']) continue;
                $isNum = true;
                foreach ($dataset['rows'] as $r) {
                    $val = str_replace(['Rp', ' ', '%', '.'], '', (string) ($r[$i] ?? ''));
                    $val = str_replace(',', '.', $val);
                    if (!is_numeric($val)) {
                        $isNum = false;
                        break;
                    }
                }
                if ($isNum) {
                    $numericCols[$i] = $colName;
                }
            }

            $v['dataset'] = $dataset;
            $v['numericCols'] = $numericCols;
            $v['palette'] = $palettes[$v['color']] ?? $palettes['sky'];
            $vizList[] = $v;
        }

        $years = array_values(array_unique($years));
        rsort($years);

        $cats = array_values(array_filter($categories, function ($c) use ($usedCategoryCodes) {
            return in_array($c['code'], $usedCategoryCodes);
        }));

        // Filter vizList if selectedYear != 'all' or selectedCat != 'all'
        $filteredViz = array_filter($vizList, function ($v) use ($selectedYear, $selectedCat) {
            $matchYr = ($selectedYear === 'all' || (string) $v['dataset']['tahun'] === $selectedYear);
            $matchCat = ($selectedCat === 'all' || (string) $v['dataset']['kategori'] === $selectedCat);
            return $matchYr && $matchCat;
        });

        return view('pages.dashboard', compact(
            'metrics',
            'filteredViz',
            'vizList',
            'years',
            'cats',
            'selectedYear',
            'selectedCat',
            'palettes'
        ));
    }
}
