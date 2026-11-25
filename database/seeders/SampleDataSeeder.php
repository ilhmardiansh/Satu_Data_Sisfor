<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Dataset;
use App\Models\User;
use App\Models\Visualization;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $wali = User::query()->where('nim', 'walidata')->first()
            ?? User::query()->first();

        if (! $wali) {
            return;
        }

        $categories = Category::query()->pluck('id', 'code');
        $defaultCategoryId = (int) Category::query()->value('id');
        if ($defaultCategoryId === 0) {
            return;
        }

        $datasetsSpec = [
            [
                'code' => 'DS-DEMO-2026-01',
                'cat_code' => 'KT-MHS-05',
                'judul' => 'Rekap Penerima Beasiswa Mahasiswa SI 2026',
                'deskripsi' => 'Sebaran penerima beasiswa mahasiswa aktif Program Studi Sistem Informasi UNUSA berdasarkan skema pendanaan.',
                'tahun' => '2026',
                'periode' => 'Ganjil',
                'tipe_akses' => 'public',
                'status_publikasi' => 'terpublikasi',
                'format' => 'XLSX',
                'kolom' => ['Skema Beasiswa', 'Jumlah Mahasiswa', 'Total Dana (Juta Rp)'],
                'baris' => [
                    ['KIP Kuliah', 42, 294],
                    ['Beasiswa Unggulan UNUSA', 28, 168],
                    ['Bank Indonesia', 14, 112],
                    ['Beasiswa Baznas', 11, 55],
                    ['Prestasi Akademik', 19, 95],
                ],
                'viz' => [
                    'code' => 'V-DEMO-01',
                    'judul' => 'Sebaran Penerima Beasiswa Mahasiswa SI (2026)',
                    'tipe_grafik' => 'donut',
                    'label_col' => 0,
                    'value_col' => 1,
                    'color' => 'sky',
                    'show' => true,
                ],
            ],
            [
                'code' => 'DS-DEMO-2025-01',
                'cat_code' => 'KT-MHS-04',
                'judul' => 'Rekap Capaian Prestasi Mahasiswa SI 2025',
                'deskripsi' => 'Jumlah tim dan mahasiswa berprestasi Program Studi Sistem Informasi UNUSA menurut tingkat kompetisi.',
                'tahun' => '2025',
                'periode' => 'Genap',
                'tipe_akses' => 'public',
                'status_publikasi' => 'terpublikasi',
                'format' => 'XLSX',
                'kolom' => ['Tingkat Kompetisi', 'Jumlah Prestasi', 'Total Mahasiswa'],
                'baris' => [
                    ['Kota / Kabupaten', 16, 38],
                    ['Provinsi', 12, 31],
                    ['Nasional', 14, 39],
                    ['Internasional', 5, 14],
                ],
                'viz' => [
                    'code' => 'V-DEMO-02',
                    'judul' => 'Capaian Prestasi Mahasiswa per Tingkat (2025)',
                    'tipe_grafik' => 'bar',
                    'label_col' => 0,
                    'value_col' => 1,
                    'color' => 'blue',
                    'show' => true,
                ],
            ],
            [
                'code' => 'DS-DEMO-2024-01',
                'cat_code' => 'KT-TRD-02',
                'judul' => 'Rekap Penelitian & Dana Hibah Dosen SI 2024',
                'deskripsi' => 'Distribusi judul penelitian dan realisasi dana hibah dosen Program Studi Sistem Informasi tahun 2024.',
                'tahun' => '2024',
                'periode' => 'Ganjil',
                'tipe_akses' => 'public',
                'status_publikasi' => 'terpublikasi',
                'format' => 'CSV',
                'kolom' => ['Skema Penelitian', 'Jumlah Judul', 'Dana Disetujui (Juta Rp)'],
                'baris' => [
                    ['Hibah Internal UNUSA', 12, 144],
                    ['DRTPM Kemendikbudristek', 6, 320],
                    ['Riset Kolaborasi Industri', 4, 185],
                    ['Pengabdian Terapan', 7, 98],
                ],
                'viz' => [
                    'code' => 'V-DEMO-03',
                    'judul' => 'Distribusi Penelitian Dosen & Pendanaan (2024)',
                    'tipe_grafik' => 'hbar',
                    'label_col' => 0,
                    'value_col' => 2,
                    'color' => 'teal',
                    'show' => true,
                ],
            ],
            [
                'code' => 'DS-DEMO-2023-01',
                'cat_code' => 'KT-MHS-06',
                'judul' => 'Sebaran Bidang Kerja Lulusan SI 2023',
                'deskripsi' => 'Hasil tracer study keterserapan lulusan S1 Sistem Informasi UNUSA berdasarkan bidang profesi.',
                'tahun' => '2023',
                'periode' => 'Genap',
                'tipe_akses' => 'public',
                'status_publikasi' => 'terpublikasi',
                'format' => 'XLSX',
                'kolom' => ['Bidang Profesi', 'Jumlah Lulusan', 'Rata-rata Masa Tunggu (Bulan)'],
                'baris' => [
                    ['Software Engineer', 24, 2],
                    ['Data Analyst & BI', 18, 2],
                    ['IT Consultant / ERP', 15, 3],
                    ['UI/UX Designer', 12, 2],
                    ['Wirausaha Digital', 9, 1],
                ],
                'viz' => [
                    'code' => 'V-DEMO-04',
                    'judul' => 'Keterserapan Bidang Kerja Alumni SI (2023)',
                    'tipe_grafik' => 'pie',
                    'label_col' => 0,
                    'value_col' => 1,
                    'color' => 'indigo',
                    'show' => true,
                ],
            ],
            [
                'code' => 'DS-DEMO-2022-01',
                'cat_code' => 'KT-KS-01',
                'judul' => 'Pertumbuhan Mitra Kerjasama Prodi SI 2022–2026',
                'deskripsi' => 'Rekap jumlah mitra aktif yang menjalin nota kesepahaman dan implementasi program dengan Prodi SI.',
                'tahun' => '2022',
                'periode' => 'Ganjil',
                'tipe_akses' => 'public',
                'status_publikasi' => 'terpublikasi',
                'format' => 'CSV',
                'kolom' => ['Tahun', 'Mitra Industri TI', 'Mitra Pemerintahan & UMKM'],
                'baris' => [
                    ['2022', 8, 6],
                    ['2023', 12, 9],
                    ['2024', 17, 13],
                    ['2025', 23, 18],
                    ['2026', 29, 22],
                ],
                'viz' => [
                    'code' => 'V-DEMO-05',
                    'judul' => 'Tren Pertumbuhan Mitra Kerjasama Prodi SI',
                    'tipe_grafik' => 'area',
                    'label_col' => 0,
                    'value_col' => 1,
                    'color' => 'cyan',
                    'show' => true,
                ],
            ],
            [
                'code' => 'DS-DEMO-2026-02',
                'cat_code' => 'KT-TRD-04',
                'judul' => 'Rekap Publikasi Ilmiah Dosen SI 2026',
                'deskripsi' => 'Data publikasi ilmiah dosen berdasarkan indeksasi jurnal dan prosiding (akses internal/private).',
                'tahun' => '2026',
                'periode' => 'Genap',
                'tipe_akses' => 'private',
                'status_publikasi' => 'terpublikasi',
                'format' => 'XLSX',
                'kolom' => ['Indeksasi', 'Jumlah Artikel', 'Sitasi'],
                'baris' => [
                    ['Scopus Q1/Q2', 9, 74],
                    ['Scopus Q3/Q4', 7, 36],
                    ['Sinta 1–2', 14, 52],
                    ['Sinta 3–4', 11, 28],
                ],
                'viz' => null,
            ],
        ];

        foreach ($datasetsSpec as $spec) {
            $categoryId = (int) ($categories[$spec['cat_code']] ?? $defaultCategoryId);

            $dataset = Dataset::query()->firstOrCreate(
                ['code' => $spec['code']],
                [
                    'id_kategori' => $categoryId,
                    'id_formulir_sumber' => null,
                    'judul' => $spec['judul'],
                    'deskripsi' => $spec['deskripsi'],
                    'tahun' => $spec['tahun'],
                    'periode' => $spec['periode'],
                    'tipe_akses' => $spec['tipe_akses'],
                    'status_publikasi' => $spec['status_publikasi'],
                    'format' => $spec['format'],
                    'label_diperbarui' => now()->locale('id')->translatedFormat('j M Y'),
                    'kolom' => $spec['kolom'],
                    'baris' => $spec['baris'],
                    'konfigurasi_sumber' => null,
                    'dibuat_oleh' => $wali->id,
                ]
            );

            if (! empty($spec['viz'])) {
                $vizSpec = $spec['viz'];
                Visualization::query()->firstOrCreate(
                    ['code' => $vizSpec['code']],
                    [
                        'id_dataset' => $dataset->id,
                        'judul' => $vizSpec['judul'],
                        'tipe_grafik' => $vizSpec['tipe_grafik'],
                        'konfigurasi_query' => [
                            'mode' => 'dataset',
                            'label_col' => $vizSpec['label_col'],
                            'value_col' => $vizSpec['value_col'],
                            'color' => $vizSpec['color'],
                            'show' => $vizSpec['show'],
                        ],
                        'dibuat_oleh' => $wali->id,
                    ]
                );
            }
        }
    }
}
