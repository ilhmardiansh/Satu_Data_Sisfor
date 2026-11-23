<?php

namespace App\Http\Controllers;

use App\Services\SatuDataService;
use Illuminate\Http\Request;

class DatasetController extends Controller
{
    public function index(Request $request)
    {
        $allDatasets = SatuDataService::getDatasets();
        $categories = SatuDataService::getCategories();

        // Calculate count for each category
        foreach ($categories as &$cat) {
            $cat['count'] = count(array_filter($allDatasets, fn($d) => $d['kategori'] === $cat['code']));
        }
        unset($cat);

        // Filters
        $kategori = (string) $request->query('kategori', 'all');
        $access = (string) $request->query('access', 'all');
        $tahun = (string) $request->query('tahun', 'all');
        $periode = (string) $request->query('periode', 'all');
        $format = (string) $request->query('format', 'all');
        $group = (string) $request->query('group', 'kategori');
        $q = trim((string) $request->query('q', ''));

        // All distinct years
        $years = array_values(array_unique(array_column($allDatasets, 'tahun')));
        rsort($years);

        // Default tracked requests from store
        $requests = [
            [
                'id' => 'R-01',
                'datasetId' => 'DS-02',
                'nama' => 'Dimas Arya',
                'email' => 'dimas@example.com',
                'hp' => '081234567890',
                'nim' => '1462100045',
                'tujuan' => 'Penelitian / tugas akhir',
                'deskripsi' => 'Data penerima KIP angkatan 2022–2023 untuk analisis retensi mahasiswa.',
                'at' => '2026-11-16T08:30:00Z',
                'status' => 'menunggu',
            ],
            [
                'id' => 'R-02',
                'datasetId' => 'DS-04',
                'nama' => 'Tim LPM UNUSA',
                'email' => 'lpm@example.com',
                'hp' => '082233445566',
                'nim' => '0099887766',
                'tujuan' => 'Akreditasi & penjaminan mutu',
                'deskripsi' => 'Rincian rekognisi mahasiswa untuk borang akreditasi.',
                'at' => '2026-11-09T10:00:00Z',
                'status' => 'disetujui',
                'decidedBy' => 'U-02',
                'decidedAt' => '2026-11-10T14:00:00Z',
            ],
        ];

        return view('pages.dataset', compact(
            'allDatasets',
            'categories',
            'years',
            'requests',
            'kategori',
            'access',
            'tahun',
            'periode',
            'format',
            'group',
            'q'
        ));
    }
}
