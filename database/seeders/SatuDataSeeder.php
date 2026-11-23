<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\AmiBenchmark;
use App\Models\AmiIndicator;
use App\Models\AmiPtkFinding;
use App\Models\AmiRecommendation;
use App\Models\AmiStandardSummary;
use App\Models\Category;
use App\Models\Course;
use App\Models\DashboardMetric;
use App\Models\DataForm;
use App\Models\DataRequest;
use App\Models\Dataset;
use App\Models\FormSubmission;
use App\Models\Lecturer;
use App\Models\NewsArticle;
use App\Models\ProgramProfile;
use App\Models\User;
use App\Models\Visualization;
use App\Services\AmiWorkbookImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SatuDataSeeder extends Seeder
{
    public function run(?int $preserveUserId = null): void
    {
        DB::transaction(function () use ($preserveUserId): void {
            $this->clearTables($preserveUserId);

            $users = $this->seedUsers($preserveUserId);
            $categories = $this->seedCategories();
            $forms = $this->seedDataForms($categories, $users['U-02']);
            $this->seedSubmissions($forms, $users);
            $datasets = $this->seedDatasets($categories, $users['U-02']);
            $this->seedRequests($datasets, $users['U-02']);
            $this->seedVisualizations($datasets, $users['U-02']);
            $this->seedAmiData($categories, $users['U-02']);
            $this->seedDashboardMetrics();
            $this->seedProgramProfile();
            $this->seedLecturers();
            $this->seedAchievements();
            $this->seedNews();
            $this->seedCourses();
        });
    }

    /**
     * @param  array<string, Category>  $categories
     */
    protected function seedAmiData(array $categories, User $creator): void
    {
        $xlsxPath = base_path(AmiWorkbookImporter::DEFAULT_WORKBOOK_PATH);
        $snapshotPath = base_path(AmiWorkbookImporter::DEFAULT_SNAPSHOT_PATH);

        if (is_file($xlsxPath) || is_file($snapshotPath)) {
            app(AmiWorkbookImporter::class)->importIntoDatabase(
                $xlsxPath,
                $creator,
                $snapshotPath,
                $categories,
                false
            );
        }
    }

    protected function clearTables(?int $preserveUserId = null): void
    {
        AmiRecommendation::query()->delete();
        AmiPtkFinding::query()->delete();
        AmiBenchmark::query()->delete();
        AmiStandardSummary::query()->delete();
        AmiIndicator::query()->delete();
        Visualization::query()->delete();
        DataRequest::query()->delete();
        FormSubmission::query()->delete();
        Dataset::query()->delete();
        DataForm::query()->delete();
        Category::query()->delete();
        DashboardMetric::query()->delete();
        Course::query()->delete();
        NewsArticle::query()->delete();
        Achievement::query()->delete();
        Lecturer::query()->delete();
        ProgramProfile::query()->delete();

        $userQuery = User::query();
        if ($preserveUserId !== null) {
            $userQuery->where('id', '!=', $preserveUserId);
        }
        $userQuery->delete();
    }

    /**
     * @return array<string, User>
     */
    protected function seedUsers(?int $preserveUserId = null): array
    {
        $seedUsers = [
            [
                'code' => 'U-01',
                'name' => 'Administrator Prodi',
                'nim' => 'admin',
                'email' => 'admin.si@unusa.ac.id',
                'password' => 'admin123',
                'role' => 'admin',
                'jenis' => null,
                'aktif' => true,
            ],
            [
                'code' => 'U-02',
                'name' => 'Rina Wulandari, M.Kom',
                'nim' => 'walidata',
                'email' => 'walidata.si@unusa.ac.id',
                'password' => 'wali123',
                'role' => 'wali',
                'jenis' => null,
                'aktif' => true,
            ],
            [
                'code' => 'U-03',
                'name' => 'Nabila Putri',
                'nim' => '1462200031',
                'email' => '1462200031@student.unusa.ac.id',
                'password' => 'mhs123',
                'role' => 'produsen',
                'jenis' => 'Mahasiswa',
                'aktif' => true,
            ],
            [
                'code' => 'U-04',
                'name' => 'Fajar Hidayat',
                'nim' => '1462300012',
                'email' => '1462300012@student.unusa.ac.id',
                'password' => 'mhs123',
                'role' => 'produsen',
                'jenis' => 'Mahasiswa',
                'aktif' => true,
            ],
            [
                'code' => 'U-05',
                'name' => 'Dr. Andi Setiawan, M.Kom',
                'nim' => '0712048601',
                'email' => 'andi.setiawan@unusa.ac.id',
                'password' => 'dosen123',
                'role' => 'produsen',
                'jenis' => 'Dosen',
                'aktif' => true,
            ],
        ];

        $preservedUser = $preserveUserId ? User::find($preserveUserId) : null;
        $map = [];

        foreach ($seedUsers as $item) {
            if ($preservedUser && $preservedUser->nim === $item['nim']) {
                $preservedUser->update([
                    'code' => $item['code'],
                    'name' => $item['name'],
                    'email' => $item['email'],
                    'role' => $item['role'],
                    'jenis' => $item['jenis'],
                    'aktif' => $item['aktif'],
                ]);
                $map[$item['code']] = $preservedUser;
                continue;
            }

            $user = User::create([
                'code' => $item['code'],
                'name' => $item['name'],
                'nim' => $item['nim'],
                'email' => $item['email'],
                'password' => Hash::make($item['password']),
                'role' => $item['role'],
                'jenis' => $item['jenis'],
                'aktif' => $item['aktif'],
            ]);
            $map[$item['code']] = $user;
        }

        return $map;
    }

    /**
     * @return array<string, Category>
     */
    protected function seedCategories(): array
    {
        $items = [
            ['code' => 'KT-MHS-05', 'name' => 'Beasiswa', 'description' => 'Penerima dan sebaran dukungan finansial mahasiswa.'],
            ['code' => 'KT-MHS-04', 'name' => 'Prestasi & Rekognisi', 'description' => 'Capaian prestasi dan rekognisi mahasiswa.'],
            ['code' => 'KT-TRD-03', 'name' => 'Pengabdian Masyarakat', 'description' => 'Kegiatan pengabdian yang diinisiasi oleh prodi.'],
            ['code' => 'KT-TRD-02', 'name' => 'Penelitian', 'description' => 'Penelitian dosen dan mahasiswa prodi.'],
            ['code' => 'KT-MHS-01', 'name' => 'Kemahasiswaan', 'description' => 'Profil, status, dan keaktifan mahasiswa.'],
            ['code' => 'KT-AKD-01', 'name' => 'Akademik', 'description' => 'Perkuliahan, IPK, dan kelulusan.'],
            ['code' => 'KT-MHS-06', 'name' => 'Alumni', 'description' => 'Tracer study dan keterserapan lulusan.'],
            ['code' => 'KT-SDM-01', 'name' => 'SDM', 'description' => 'Dosen dan tenaga kependidikan.'],
            ['code' => 'KT-TRD-04', 'name' => 'Publikasi Ilmiah', 'description' => 'Jurnal, prosiding, dan HKI.'],
            ['code' => 'KT-KS-01', 'name' => 'Kerjasama', 'description' => 'Mitra dan dokumen kerja sama.'],
            ['code' => 'KT-MTU-01', 'name' => 'Akreditasi', 'description' => 'Dokumen dan capaian akreditasi.'],
            ['code' => 'KT-KEU-01', 'name' => 'Keuangan', 'description' => 'Anggaran dan realisasi prodi.'],
            ['code' => 'KT-AMI-01', 'name' => 'Audit Mutu Internal (AMI)', 'description' => 'Instrumen SNPT-U 28 & 38 standar, benchmark 16 prodi UNUSA, Permintaan Tindakan Koreksi (PTK), dan saran pengembangan Prodi S1 Sistem Informasi.'],
        ];

        $map = [];
        foreach ($items as $item) {
            $map[$item['code']] = Category::create($item);
        }

        return $map;
    }

    /**
     * @param  array<string, Category>  $categories
     * @return array<string, DataForm>
     */
    protected function seedDataForms(array $categories, User $creator): array
    {
        $items = [
            [
                'code' => 'F-01',
                'category_id' => $categories['KT-MHS-04']->id,
                'title' => 'Pelaporan Prestasi Mahasiswa 2026',
                'description' => 'Laporkan prestasi lomba atau rekognisi yang diraih selama semester berjalan beserta bukti sertifikat.',
                'tahun' => 2026,
                'periode' => 'Ganjil',
                'target' => 'Mahasiswa',
                'deadline' => now()->addDays(10),
                'created_at' => now()->subDays(12),
                'fields' => [
                    ['id' => 'q1', 'label' => 'Nama kompetisi', 'type' => 'text', 'required' => true],
                    ['id' => 'q2', 'label' => 'Tingkat', 'type' => 'select', 'required' => true, 'options' => ['Regional', 'Nasional', 'Internasional']],
                    ['id' => 'q3', 'label' => 'Peringkat', 'type' => 'text', 'required' => true],
                    ['id' => 'q4', 'label' => 'Tanggal pelaksanaan', 'type' => 'date', 'required' => true],
                    ['id' => 'q5', 'label' => 'Sertifikat / bukti', 'type' => 'file', 'required' => true],
                ],
            ],
            [
                'code' => 'F-02',
                'category_id' => $categories['KT-MHS-05']->id,
                'title' => 'Pendataan Penerima Beasiswa',
                'description' => 'Pendataan mahasiswa penerima beasiswa aktif tahun akademik 2026/2027.',
                'tahun' => 2026,
                'periode' => 'Ganjil',
                'target' => 'Mahasiswa',
                'deadline' => now()->addDays(2),
                'created_at' => now()->subDays(7),
                'fields' => [
                    ['id' => 'q1', 'label' => 'Jenis beasiswa', 'type' => 'select', 'required' => true, 'options' => ['KIP Kuliah', 'Prestasi', 'Yayasan', 'Mitra Industri']],
                    ['id' => 'q2', 'label' => 'Tahun mulai menerima', 'type' => 'number', 'required' => true],
                    ['id' => 'q3', 'label' => 'SK penerima beasiswa', 'type' => 'file', 'required' => true],
                ],
            ],
            [
                'code' => 'F-03',
                'category_id' => $categories['KT-TRD-02']->id,
                'title' => 'Laporan Penelitian Dosen 2026',
                'description' => 'Pelaporan judul, skema, pendanaan, dan luaran penelitian dosen.',
                'tahun' => 2026,
                'periode' => 'Ganjil',
                'target' => 'Dosen',
                'deadline' => now()->addDays(20),
                'created_at' => now()->subDays(5),
                'fields' => [
                    ['id' => 'q1', 'label' => 'Judul penelitian', 'type' => 'text', 'required' => true],
                    ['id' => 'q2', 'label' => 'Skema pendanaan', 'type' => 'select', 'required' => true, 'options' => ['Hibah eksternal', 'Internal', 'Mitra']],
                    ['id' => 'q3', 'label' => 'Jumlah dana (Rp)', 'type' => 'number', 'required' => true],
                    ['id' => 'q4', 'label' => 'Luaran', 'type' => 'textarea', 'required' => false],
                    ['id' => 'q5', 'label' => 'Kontrak penelitian', 'type' => 'file', 'required' => false],
                ],
            ],
            [
                'code' => 'F-04',
                'category_id' => $categories['KT-TRD-03']->id,
                'title' => 'Kegiatan Pengabdian Masyarakat 2025',
                'description' => 'Pendataan kegiatan PKM semester genap 2025.',
                'tahun' => 2025,
                'periode' => 'Genap',
                'target' => 'Dosen',
                'deadline' => now()->subDays(5),
                'created_at' => now()->subDays(40),
                'fields' => [
                    ['id' => 'q1', 'label' => 'Judul kegiatan', 'type' => 'text', 'required' => true],
                    ['id' => 'q2', 'label' => 'Mitra', 'type' => 'text', 'required' => true],
                    ['id' => 'q3', 'label' => 'Jumlah peserta', 'type' => 'number', 'required' => true],
                ],
            ],
        ];

        $map = [];
        foreach ($items as $item) {
            $createdAt = $item['created_at'];
            unset($item['created_at']);
            $form = new DataForm($item);
            $form->created_by = $creator->id;
            $form->created_at = $createdAt;
            $form->updated_at = $createdAt;
            $form->save();
            $map[$item['code']] = $form;
        }

        return $map;
    }

    /**
     * @param  array<string, DataForm>  $forms
     * @param  array<string, User>  $users
     */
    protected function seedSubmissions(array $forms, array $users): void
    {
        $items = [
            [
                'code' => 'S-01',
                'data_form_id' => $forms['F-01']->id,
                'user_id' => $users['U-03']->id,
                'submitted_at' => now()->subDays(6),
                'values' => [
                    'q1' => 'Gemastik UI/UX',
                    'q2' => 'Nasional',
                    'q3' => 'Juara 1',
                    'q4' => '2026-09-14',
                    'q5' => 'sertifikat-gemastik.pdf',
                ],
            ],
            [
                'code' => 'S-02',
                'data_form_id' => $forms['F-01']->id,
                'user_id' => $users['U-04']->id,
                'submitted_at' => now()->subDays(4),
                'values' => [
                    'q1' => 'Hackathon Jatim',
                    'q2' => 'Regional',
                    'q3' => 'Juara 2',
                    'q4' => '2026-09-28',
                    'q5' => 'piagam-hackathon.jpg',
                ],
            ],
            [
                'code' => 'S-03',
                'data_form_id' => $forms['F-02']->id,
                'user_id' => $users['U-03']->id,
                'submitted_at' => now()->subDays(3),
                'values' => [
                    'q1' => 'KIP Kuliah',
                    'q2' => '2022',
                    'q3' => 'sk-kip-2022.pdf',
                ],
            ],
            [
                'code' => 'S-04',
                'data_form_id' => $forms['F-03']->id,
                'user_id' => $users['U-05']->id,
                'submitted_at' => now()->subDays(2),
                'values' => [
                    'q1' => 'Prediksi retensi mahasiswa berbasis ML',
                    'q2' => 'Hibah eksternal',
                    'q3' => '75000000',
                    'q4' => 'Artikel jurnal Q1',
                    'q5' => 'kontrak-penelitian.pdf',
                ],
            ],
            [
                'code' => 'S-05',
                'data_form_id' => $forms['F-04']->id,
                'user_id' => $users['U-05']->id,
                'submitted_at' => now()->subDays(20),
                'values' => [
                    'q1' => 'Digitalisasi pencatatan UMKM',
                    'q2' => 'Desa Wonocolo',
                    'q3' => '35',
                ],
            ],
        ];

        foreach ($items as $item) {
            FormSubmission::create($item);
        }
    }

    /**
     * @param  array<string, Category>  $categories
     * @return array<string, Dataset>
     */
    protected function seedDatasets(array $categories, User $creator): array
    {
        $items = [
            [
                'code' => 'DS-01',
                'tahun' => 2026,
                'periode' => 'Ganjil',
                'kategori' => 'KT-MHS-05',
                'access' => 'public',
                'title' => 'Rekap penerima beasiswa per tahun',
                'description' => 'Jumlah penerima beasiswa berdasarkan jenis dan tahun akademik.',
                'updated_label' => '10 Okt 2026',
                'format' => 'CSV',
                'cols' => ['Tahun', 'KIP Kuliah', 'Prestasi', 'Yayasan', 'Mitra Industri', 'Total'],
                'rows' => [
                    ['2022', 26, 8, 7, 3, 44],
                    ['2023', 29, 9, 7, 4, 49],
                    ['2024', 33, 10, 8, 5, 56],
                    ['2025', 36, 11, 9, 6, 62],
                    ['2026', 38, 12, 9, 7, 66],
                ],
            ],
            [
                'code' => 'DS-02',
                'tahun' => 2026,
                'periode' => 'Ganjil',
                'kategori' => 'KT-MHS-05',
                'access' => 'private',
                'title' => 'Daftar nama penerima KIP Kuliah',
                'description' => 'Data individu penerima KIP Kuliah beserta status keaktifan.',
                'updated_label' => '8 Okt 2026',
                'format' => 'XLSX',
                'cols' => ['NIM', 'Nama', 'Angkatan', 'Status'],
                'rows' => [
                    ['1462200001', 'Ahmad Fauzi', 2022, 'Aktif'],
                    ['1462200014', 'Siti Nurhaliza', 2022, 'Aktif'],
                    ['1462300007', 'Rizky Pratama', 2023, 'Aktif'],
                    ['1462300021', 'Dewi Lestari', 2023, 'Cuti'],
                    ['1462400003', 'M. Iqbal', 2024, 'Aktif'],
                ],
            ],
            [
                'code' => 'DS-03',
                'tahun' => 2025,
                'periode' => 'Genap',
                'kategori' => 'KT-MHS-04',
                'access' => 'public',
                'title' => 'Prestasi mahasiswa per tingkat',
                'description' => 'Jumlah prestasi berdasarkan tingkat kompetisi dan bidang.',
                'updated_label' => '2 Okt 2026',
                'format' => 'CSV',
                'cols' => ['Tingkat', 'Akademik', 'Non-akademik', 'Total'],
                'rows' => [
                    ['Internasional', 4, 2, 6],
                    ['Nasional', 15, 8, 23],
                    ['Regional', 11, 8, 19],
                ],
            ],
            [
                'code' => 'DS-04',
                'tahun' => 2025,
                'periode' => 'Ganjil',
                'kategori' => 'KT-MHS-04',
                'access' => 'private',
                'title' => 'Rincian sertifikat & rekognisi mahasiswa',
                'description' => 'Detail lomba, peringkat, dan bukti sertifikat per mahasiswa.',
                'updated_label' => '1 Okt 2026',
                'format' => 'XLSX',
                'cols' => ['NIM', 'Nama', 'Kompetisi', 'Peringkat'],
                'rows' => [
                    ['1462200031', 'Nabila Putri', 'Gemastik UI/UX', 'Juara 1'],
                    ['1462300012', 'Fajar Hidayat', 'Hackathon Jatim', 'Juara 2'],
                    ['1462300044', 'Laila Rahma', 'Business Plan Nasional', 'Finalis'],
                ],
            ],
            [
                'code' => 'DS-05',
                'tahun' => 2026,
                'periode' => 'Ganjil',
                'kategori' => 'KT-TRD-03',
                'access' => 'public',
                'title' => 'Kegiatan pengabdian masyarakat',
                'description' => 'Judul, mitra, dan lokasi kegiatan PKM dosen prodi.',
                'updated_label' => '25 Sep 2026',
                'format' => 'CSV',
                'cols' => ['Tahun', 'Judul', 'Mitra', 'Lokasi'],
                'rows' => [
                    ['2025', 'Digitalisasi pencatatan UMKM', 'Desa Wonocolo', 'Surabaya'],
                    ['2025', 'Literasi data untuk guru SMA', 'MGMP TIK', 'Sidoarjo'],
                    ['2026', 'Sistem inventaris koperasi', 'Koperasi Al-Falah', 'Gresik'],
                ],
            ],
            [
                'code' => 'DS-06',
                'tahun' => 2025,
                'periode' => 'Genap',
                'kategori' => 'KT-TRD-03',
                'access' => 'private',
                'title' => 'Laporan keuangan dana PKM',
                'description' => 'Realisasi anggaran kegiatan pengabdian per judul.',
                'updated_label' => '20 Sep 2026',
                'format' => 'XLSX',
                'cols' => ['Judul', 'Sumber dana', 'Anggaran', 'Realisasi'],
                'rows' => [
                    ['Digitalisasi pencatatan UMKM', 'Internal', 'Rp 15.000.000', 'Rp 14.200.000'],
                    ['Literasi data untuk guru SMA', 'Hibah Dikti', 'Rp 40.000.000', 'Rp 38.750.000'],
                ],
            ],
            [
                'code' => 'DS-07',
                'tahun' => 2025,
                'periode' => 'Ganjil',
                'kategori' => 'KT-TRD-02',
                'access' => 'public',
                'title' => 'Rekap penelitian per skema',
                'description' => 'Jumlah judul penelitian dosen per sumber pendanaan.',
                'updated_label' => '18 Sep 2026',
                'format' => 'CSV',
                'cols' => ['Tahun', 'Hibah eksternal', 'Internal', 'Mitra', 'Total'],
                'rows' => [
                    ['2023', 4, 8, 2, 14],
                    ['2024', 5, 9, 3, 17],
                    ['2025', 6, 10, 3, 19],
                ],
            ],
            [
                'code' => 'DS-08',
                'tahun' => 2024,
                'periode' => 'Genap',
                'kategori' => 'KT-TRD-02',
                'access' => 'private',
                'title' => 'Proposal & luaran penelitian dosen',
                'description' => 'Detail judul, ketua peneliti, dana, dan luaran penelitian.',
                'updated_label' => '15 Sep 2026',
                'format' => 'XLSX',
                'cols' => ['Judul', 'Ketua', 'Dana', 'Luaran'],
                'rows' => [
                    ['Prediksi retensi mahasiswa berbasis ML', 'Dr. Andi S.', 'Rp 75.000.000', 'Jurnal Q1'],
                    ['Model adopsi e-government desa', 'Rina W., M.Kom', 'Rp 20.000.000', 'Sinta 2'],
                ],
            ],
            [
                'code' => 'DS-09',
                'tahun' => 2025,
                'periode' => 'Ganjil',
                'kategori' => 'KT-MHS-05',
                'access' => 'public',
                'title' => 'Rekap penerima beasiswa 2025',
                'description' => 'Penerima beasiswa tahun akademik 2025 per jenis.',
                'updated_label' => '12 Feb 2025',
                'format' => 'CSV',
                'cols' => ['Jenis', 'Laki-laki', 'Perempuan', 'Total'],
                'rows' => [
                    ['KIP Kuliah', 16, 20, 36],
                    ['Prestasi', 5, 6, 11],
                    ['Yayasan', 4, 5, 9],
                    ['Mitra Industri', 3, 3, 6],
                ],
            ],
            [
                'code' => 'DS-10',
                'tahun' => 2024,
                'periode' => 'Genap',
                'kategori' => 'KT-MHS-04',
                'access' => 'public',
                'title' => 'Prestasi mahasiswa 2024',
                'description' => 'Rekap prestasi mahasiswa tahun 2024 per tingkat.',
                'updated_label' => '20 Jan 2025',
                'format' => 'CSV',
                'cols' => ['Tingkat', 'Akademik', 'Non-akademik', 'Total'],
                'rows' => [
                    ['Internasional', 3, 2, 5],
                    ['Nasional', 12, 7, 19],
                    ['Regional', 9, 7, 16],
                ],
            ],
            [
                'code' => 'DS-11',
                'tahun' => 2024,
                'periode' => 'Ganjil',
                'kategori' => 'KT-TRD-03',
                'access' => 'public',
                'title' => 'Kegiatan pengabdian masyarakat 2024',
                'description' => 'Daftar kegiatan PKM dosen prodi tahun 2024.',
                'updated_label' => '15 Des 2024',
                'format' => 'CSV',
                'cols' => ['Tahun', 'Judul', 'Mitra', 'Lokasi'],
                'rows' => [
                    ['2024', 'Pelatihan website UMKM', 'Kel. Jemur Wonosari', 'Surabaya'],
                    ['2024', 'Aplikasi kas masjid', 'Takmir Al-Ikhlas', 'Sidoarjo'],
                ],
            ],
            [
                'code' => 'DS-12',
                'tahun' => 2026,
                'periode' => 'Genap',
                'kategori' => 'KT-TRD-02',
                'access' => 'private',
                'title' => 'Usulan penelitian 2026',
                'description' => 'Usulan penelitian dosen yang sedang diproses.',
                'updated_label' => '3 Okt 2026',
                'format' => 'XLSX',
                'cols' => ['Judul', 'Ketua', 'Skema', 'Status'],
                'rows' => [
                    ['Deteksi fraud transaksi digital', 'Dr. Andi S.', 'Hibah Dikti', 'Review'],
                    ['Chatbot layanan akademik', 'Rina W., M.Kom', 'Internal', 'Diterima'],
                ],
            ],
        ];

        $map = [];
        foreach ($items as $item) {
            $kategori = $item['kategori'];
            unset($item['kategori']);

            $item['category_id'] = $categories[$kategori]->id;
            $item['created_by'] = $creator->id;
            $map[$item['code']] = Dataset::create($item);
        }

        return $map;
    }

    /**
     * @param  array<string, Dataset>  $datasets
     */
    protected function seedRequests(array $datasets, User $decider): void
    {
        DataRequest::create([
            'code' => 'R-01',
            'dataset_id' => $datasets['DS-02']->id,
            'nama' => 'Dimas Arya',
            'email' => 'dimas@example.com',
            'hp' => '081234567890',
            'nim' => '1462100045',
            'tujuan' => 'Penelitian / tugas akhir',
            'deskripsi' => 'Data penerima KIP angkatan 2022–2023 untuk analisis retensi mahasiswa.',
            'status' => 'menunggu',
            'requested_at' => now()->subDay(),
        ]);

        DataRequest::create([
            'code' => 'R-02',
            'dataset_id' => $datasets['DS-04']->id,
            'nama' => 'Tim LPM UNUSA',
            'email' => 'lpm@example.com',
            'hp' => '082233445566',
            'nim' => '0099887766',
            'tujuan' => 'Akreditasi & penjaminan mutu',
            'deskripsi' => 'Rincian rekognisi mahasiswa untuk borang akreditasi.',
            'status' => 'disetujui',
            'decided_by' => $decider->id,
            'decided_at' => now()->subDays(7),
            'requested_at' => now()->subDays(8),
        ]);
    }

    /**
     * @param  array<string, Dataset>  $datasets
     */
    protected function seedVisualizations(array $datasets, User $creator): void
    {
        $items = [
            [
                'code' => 'V-01',
                'title' => 'Total penerima beasiswa per tahun',
                'dataset_id' => $datasets['DS-01']->id,
                'label_col' => 0,
                'value_col' => 5,
                'kind' => 'bar',
                'color' => 'sky',
                'show' => true,
                'created_by' => $creator->id,
            ],
            [
                'code' => 'V-02',
                'title' => 'Prestasi mahasiswa per tingkat',
                'dataset_id' => $datasets['DS-03']->id,
                'label_col' => 0,
                'value_col' => 3,
                'kind' => 'donut',
                'color' => 'violet',
                'show' => true,
                'created_by' => $creator->id,
            ],
            [
                'code' => 'V-03',
                'title' => 'Tren penelitian dosen',
                'dataset_id' => $datasets['DS-07']->id,
                'label_col' => 0,
                'value_col' => 4,
                'kind' => 'line',
                'color' => 'teal',
                'show' => true,
                'created_by' => $creator->id,
            ],
        ];

        foreach ($items as $item) {
            Visualization::create($item);
        }
    }

    protected function seedDashboardMetrics(): void
    {
        $metrics = [
            // A.1 Pendidikan dan mahasiswa
            [
                'section' => 'akademik1',
                'sort_order' => 1,
                'title' => 'Mahasiswa Aktif',
                'color' => 'sky',
                'total' => '842',
                'unit' => 'mahasiswa aktif',
                'default_kind' => 'donut',
                'trend_value' => null,
                'trend_up' => null,
                'data' => [
                    ['n' => 'Angkatan 2026', 'v' => 204],
                    ['n' => 'Angkatan 2025', 'v' => 196],
                    ['n' => 'Angkatan 2024', 'v' => 183],
                    ['n' => 'Angkatan 2023', 'v' => 171],
                    ['n' => '≤ 2022', 'v' => 88],
                ],
                'foot' => [
                    ['Maba 2026', '204'],
                    ['Lulus tepat waktu', '71,2%'],
                ],
                'table_data' => [
                    'heading' => 'Mahasiswa aktif per angkatan dan jenis kelamin',
                    'label' => 'Angkatan',
                    'cols' => ['Laki-laki', 'Perempuan'],
                    'rows' => [
                        ['2026', 112, 92],
                        ['2025', 105, 91],
                        ['2024', 99, 84],
                        ['2023', 93, 78],
                        ['2022 dan sebelumnya', 55, 33],
                    ],
                ],
            ],
            [
                'section' => 'akademik1',
                'sort_order' => 2,
                'title' => 'Kategori Mahasiswa',
                'color' => 'blue',
                'total' => '237',
                'unit' => 'mahasiswa 2026',
                'default_kind' => 'donut',
                'trend_value' => '4,2% vs 2025',
                'trend_up' => true,
                'data' => [
                    ['n' => 'Reguler', 'v' => 171],
                    ['n' => 'Beasiswa', 'v' => 66],
                ],
                'foot' => [
                    ['Rasio beasiswa', '27,8%'],
                ],
                'table_data' => [
                    'heading' => 'Mahasiswa baru per tahun masuk berdasarkan kategori',
                    'label' => 'Tahun masuk',
                    'cols' => ['Reguler', 'Beasiswa'],
                    'rows' => [
                        ['2022', 142, 44],
                        ['2023', 148, 49],
                        ['2024', 157, 56],
                        ['2025', 165, 62],
                        ['2026', 171, 66],
                    ],
                ],
            ],
            [
                'section' => 'akademik1',
                'sort_order' => 3,
                'title' => 'Penerimaan Beasiswa',
                'color' => 'indigo',
                'total' => '66',
                'unit' => 'penerima',
                'default_kind' => 'donut',
                'trend_value' => '6,5% vs 2025',
                'trend_up' => true,
                'data' => [
                    ['n' => 'KIP Kuliah', 'v' => 38],
                    ['n' => 'Prestasi', 'v' => 12],
                    ['n' => 'Yayasan', 'v' => 9],
                    ['n' => 'Mitra Industri', 'v' => 5],
                    ['n' => 'Lainnya', 'v' => 2],
                ],
                'foot' => null,
                'table_data' => [
                    'heading' => 'Penerima beasiswa per jenis dan jenis kelamin',
                    'label' => 'Jenis beasiswa',
                    'cols' => ['Laki-laki', 'Perempuan'],
                    'rows' => [
                        ['KIP Kuliah', 17, 21],
                        ['Prestasi', 5, 7],
                        ['Yayasan', 4, 5],
                        ['Mitra Industri', 3, 2],
                        ['Lainnya', 1, 1],
                    ],
                ],
            ],
            [
                'section' => 'akademik1',
                'sort_order' => 4,
                'title' => 'Alumni',
                'color' => 'cyan',
                'total' => '1.214',
                'unit' => 'responden tracer',
                'default_kind' => 'donut',
                'trend_value' => null,
                'trend_up' => null,
                'data' => [
                    ['n' => 'Bekerja', 'v' => 742],
                    ['n' => 'Wirausaha', 'v' => 128],
                    ['n' => 'Studi lanjut', 'v' => 96],
                    ['n' => 'Mencari kerja', 'v' => 210],
                    ['n' => 'Belum memberi', 'v' => 38],
                ],
                'foot' => [
                    ['Masa tunggu', '3,4 bulan'],
                ],
                'table_data' => [
                    'heading' => 'Status alumni per tahun lulus (tracer study)',
                    'label' => 'Tahun lulus',
                    'cols' => ['Bekerja', 'Wirausaha', 'Studi lanjut', 'Mencari kerja', 'Belum memberi'],
                    'rows' => [
                        ['2021', 128, 22, 18, 30, 6],
                        ['2022', 140, 24, 19, 36, 7],
                        ['2023', 150, 26, 20, 42, 8],
                        ['2024', 158, 27, 19, 48, 8],
                        ['2025', 166, 29, 20, 54, 9],
                    ],
                ],
            ],

            // A.2 Penelitian, prestasi, dan luaran
            [
                'section' => 'akademik2',
                'sort_order' => 1,
                'title' => 'Mahasiswa Berprestasi',
                'color' => 'violet',
                'total' => '48',
                'unit' => 'prestasi 2025',
                'default_kind' => 'donut',
                'trend_value' => '20% vs 2024',
                'trend_up' => true,
                'data' => [
                    ['n' => 'Internasional', 'v' => 6],
                    ['n' => 'Nasional', 'v' => 23],
                    ['n' => 'Regional', 'v' => 19],
                ],
                'foot' => null,
                'table_data' => [
                    'heading' => 'Prestasi mahasiswa per tingkat dan bidang',
                    'label' => 'Tingkat',
                    'cols' => ['Akademik', 'Non-akademik'],
                    'rows' => [
                        ['Internasional', 4, 2],
                        ['Nasional', 15, 8],
                        ['Regional', 11, 8],
                    ],
                ],
            ],
            [
                'section' => 'akademik2',
                'sort_order' => 2,
                'title' => 'Penelitian & PKM',
                'color' => 'teal',
                'total' => '34',
                'unit' => 'judul 2025',
                'default_kind' => 'donut',
                'trend_value' => '13,3% vs 2024',
                'trend_up' => true,
                'data' => [
                    ['n' => 'Penelitian', 'v' => 19],
                    ['n' => 'Pengabdian', 'v' => 15],
                ],
                'foot' => [
                    ['Dana hibah', 'Rp 1,2 M'],
                ],
                'table_data' => [
                    'heading' => 'Judul penelitian dan pengabdian per sumber dana',
                    'label' => 'Kegiatan',
                    'cols' => ['Hibah eksternal', 'Internal', 'Mitra'],
                    'rows' => [
                        ['Penelitian', 6, 10, 3],
                        ['Pengabdian (PKM)', 4, 10, 1],
                    ],
                ],
            ],
            [
                'section' => 'akademik2',
                'sort_order' => 3,
                'title' => 'Publikasi Ilmiah',
                'color' => 'sky',
                'total' => '63',
                'unit' => 'publikasi 2025',
                'default_kind' => 'donut',
                'trend_value' => '14,5% vs 2024',
                'trend_up' => true,
                'data' => [
                    ['n' => 'Jurnal Sinta', 'v' => 31],
                    ['n' => 'Jurnal Scopus', 'v' => 10],
                    ['n' => 'Prosiding', 'v' => 22],
                ],
                'foot' => [
                    ['Publikasi / dosen', '2,42'],
                ],
                'table_data' => [
                    'heading' => 'Publikasi per jenis dan keterlibatan penulis',
                    'label' => 'Jenis publikasi',
                    'cols' => ['Dosen', 'Dosen + mahasiswa'],
                    'rows' => [
                        ['Jurnal Sinta', 20, 11],
                        ['Jurnal Scopus', 7, 3],
                        ['Prosiding', 12, 10],
                    ],
                ],
            ],
            [
                'section' => 'akademik2',
                'sort_order' => 4,
                'title' => 'Sebaran IPK',
                'color' => 'blue',
                'total' => '3,41',
                'unit' => 'rata-rata IPK',
                'default_kind' => 'donut',
                'trend_value' => null,
                'trend_up' => null,
                'data' => [
                    ['n' => '< 2,5', 'v' => 38],
                    ['n' => '2,5–3,0', 'v' => 126],
                    ['n' => '3,0–3,5', 'v' => 384],
                    ['n' => '3,5–4,0', 'v' => 294],
                ],
                'foot' => null,
                'table_data' => [
                    'heading' => 'Sebaran IPK mahasiswa aktif per jenis kelamin',
                    'label' => 'Rentang IPK',
                    'cols' => ['Laki-laki', 'Perempuan'],
                    'rows' => [
                        ['< 2,50', 26, 12],
                        ['2,50 – 3,00', 74, 52],
                        ['3,00 – 3,50', 208, 176],
                        ['3,50 – 4,00', 156, 138],
                    ],
                ],
            ],

            // N.1 Sumber daya
            [
                'section' => 'nonakademik1',
                'sort_order' => 1,
                'title' => 'Dosen',
                'color' => 'sky',
                'total' => '26',
                'unit' => 'dosen aktif',
                'default_kind' => 'donut',
                'trend_value' => null,
                'trend_up' => null,
                'data' => [
                    ['n' => 'Tetap', 'v' => 23],
                    ['n' => 'Tidak tetap', 'v' => 3],
                ],
                'foot' => [
                    ['Rasio dosen : mhs', '1 : 32,4'],
                    ['Kualifikasi S3', '11 (42%)'],
                ],
                'table_data' => [
                    'heading' => 'Dosen per status dan pendidikan tertinggi',
                    'label' => 'Status',
                    'cols' => ['S2', 'S3'],
                    'rows' => [
                        ['Dosen tetap', 12, 11],
                        ['Dosen tidak tetap', 3, 0],
                    ],
                ],
            ],
            [
                'section' => 'nonakademik1',
                'sort_order' => 2,
                'title' => 'Jabatan Fungsional',
                'color' => 'indigo',
                'total' => '26',
                'unit' => 'dosen',
                'default_kind' => 'donut',
                'trend_value' => null,
                'trend_up' => null,
                'data' => [
                    ['n' => 'Tenaga Pengajar', 'v' => 3],
                    ['n' => 'Asisten Ahli', 'v' => 7],
                    ['n' => 'Lektor', 'v' => 10],
                    ['n' => 'Lektor Kepala', 'v' => 5],
                    ['n' => 'Profesor', 'v' => 1],
                ],
                'foot' => null,
                'table_data' => [
                    'heading' => 'Dosen per jabatan fungsional dan pendidikan',
                    'label' => 'Jabatan',
                    'cols' => ['S2', 'S3'],
                    'rows' => [
                        ['Tenaga Pengajar', 3, 0],
                        ['Asisten Ahli', 6, 1],
                        ['Lektor', 5, 5],
                        ['Lektor Kepala', 1, 4],
                        ['Profesor', 0, 1],
                    ],
                ],
            ],
            [
                'section' => 'nonakademik1',
                'sort_order' => 3,
                'title' => 'Tenaga Kependidikan',
                'color' => 'cyan',
                'total' => '7',
                'unit' => 'tendik',
                'default_kind' => 'donut',
                'trend_value' => null,
                'trend_up' => null,
                'data' => [
                    ['n' => 'Admin Prodi', 'v' => 3],
                    ['n' => 'Laboran', 'v' => 3],
                    ['n' => 'Pustakawan', 'v' => 1],
                ],
                'foot' => null,
                'table_data' => [
                    'heading' => 'Tenaga kependidikan per unit dan status',
                    'label' => 'Posisi',
                    'cols' => ['Tetap', 'Kontrak'],
                    'rows' => [
                        ['Admin Prodi', 2, 1],
                        ['Laboran', 2, 1],
                        ['Pustakawan', 1, 0],
                    ],
                ],
            ],

            // N.2 Tata kelola dan kemitraan
            [
                'section' => 'nonakademik2',
                'sort_order' => 1,
                'title' => 'Kerjasama',
                'color' => 'blue',
                'total' => '24',
                'unit' => 'kerja sama aktif',
                'default_kind' => 'donut',
                'trend_value' => null,
                'trend_up' => null,
                'data' => [
                    ['n' => 'Industri', 'v' => 11],
                    ['n' => 'Pemerintah', 'v' => 4],
                    ['n' => 'PT dalam negeri', 'v' => 5],
                    ['n' => 'Luar negeri', 'v' => 3],
                    ['n' => 'Lainnya', 'v' => 1],
                ],
                'foot' => [
                    ['Habis tahun ini', '4'],
                ],
                'table_data' => [
                    'heading' => 'Kerja sama aktif per mitra dan jenis dokumen',
                    'label' => 'Jenis mitra',
                    'cols' => ['MoU', 'MoA', 'IA'],
                    'rows' => [
                        ['Industri', 3, 5, 3],
                        ['Pemerintah', 2, 1, 1],
                        ['PT dalam negeri', 2, 2, 1],
                        ['Luar negeri', 1, 1, 1],
                        ['Lainnya', 1, 0, 0],
                    ],
                ],
            ],
        ];

        foreach ($metrics as $item) {
            DashboardMetric::create($item);
        }
    }

    protected function seedProgramProfile(): void
    {
        ProgramProfile::create([
            'deskripsi' => 'Program Studi Sistem Informasi Universitas Nahdlatul Ulama Surabaya menyiapkan lulusan yang mampu merancang, membangun, dan mengelola sistem informasi untuk mendukung proses bisnis organisasi dengan berlandaskan nilai-nilai Ahlussunnah wal Jamaah.',
            'visi' => 'Menjadi program studi Sistem Informasi yang unggul dalam pengembangan sistem informasi bisnis dan tata kelola data berbasis nilai Islam Ahlussunnah wal Jamaah di tingkat nasional pada tahun 2030.',
            'misi' => [
                'Menyelenggarakan pendidikan sistem informasi yang berkualitas dan relevan dengan kebutuhan industri.',
                'Melaksanakan penelitian di bidang sistem informasi, analitika data, dan transformasi digital.',
                'Melaksanakan pengabdian kepada masyarakat melalui penerapan teknologi informasi tepat guna.',
                'Menjalin kerja sama dengan pemerintah, industri, dan perguruan tinggi di dalam maupun luar negeri.',
            ],
            'akreditasi' => 'Baik Sekali',
            'berdiri' => '2013',
            'alamat' => 'Kampus B UNUSA, Jl. Jemursari No. 51–57, Surabaya, Jawa Timur 60237',
            'email' => 'si@unusa.ac.id',
            'telp' => '(031) 8291920',
            'sejarah' => "Program Studi Sistem Informasi Universitas Nahdlatul Ulama Surabaya (UNUSA) didirikan pada tahun 2013 untuk menjawab kebutuhan tenaga profesional di bidang teknologi informasi, analitika data, dan manajemen sistem bisnis di Jawa Timur dan Indonesia.\n\nSejak awal pendiriannya, program studi ini terus mengembangkan kurikulum berbasis proyek serta tata kelola data terintegrasi yang memadukan kompetensi teknologi informasi dengan nilai-nilai Islam Ahlussunnah wal Jamaah.",
            'program' => 'S1 Sistem Informasi',
            'gelar' => 'Sarjana Komputer (S.Kom)',
            'durasi' => '8 Semester (4 Tahun)',
            'syarat' => 'Lulusan SMA/SMK/MA atau sederajat yang memenuhi syarat seleksi penerimaan mahasiswa baru UNUSA.',
            'fakultas' => 'Fakultas Ekonomi Bisnis dan Teknologi Digital',
            'fasilitas' => 'Laboratorium Rekayasa Perangkat Lunak, Lab Analitika Data & AI, Smart Classroom, Perpustakaan Terpadu, serta Ruang Inkubasi Bisnis Digital.',
        ]);
    }

    protected function seedLecturers(): void
    {
        $items = [
            [
                'code' => 'D-01',
                'nama' => 'Dr. Andi Setiawan, M.Kom',
                'nidn' => '0712048601',
                'jabatan' => 'Lektor Kepala',
                'pendidikan' => 'S3',
                'bidang' => 'Machine Learning',
                'foto' => 'https://images.unsplash.com/photo-1600896997793-b8ed3459a17f?auto=format&fit=crop&crop=faces&w=400&h=400&q=80',
                'email' => 'andi.setiawan@unusa.ac.id',
                'mata_kuliah' => 'Machine Learning, Analitika Data, Metodologi Penelitian',
                'profil' => 'Peneliti dan dosen senior bidang Machine Learning terapan serta analitika data pendidikan tinggi.',
                'sort_order' => 1,
            ],
            [
                'code' => 'D-02',
                'nama' => 'Rina Wulandari, M.Kom',
                'nidn' => '0703108802',
                'jabatan' => 'Lektor',
                'pendidikan' => 'S2',
                'bidang' => 'E-Government',
                'foto' => 'https://images.unsplash.com/photo-1585917170055-e40d30c73414?auto=format&fit=crop&crop=faces&w=400&h=400&q=80',
                'email' => 'rina.wulandari@unusa.ac.id',
                'mata_kuliah' => 'E-Government, Proses Bisnis, Sistem Informasi Manajemen',
                'profil' => 'Berfokus pada transformasi digital pemerintahan daerah dan adopsi layanan publik berbasis data.',
                'sort_order' => 2,
            ],
            [
                'code' => 'D-03',
                'nama' => 'Dr. Moch. Yusuf Hidayat, M.T',
                'nidn' => '0721078503',
                'jabatan' => 'Lektor',
                'pendidikan' => 'S3',
                'bidang' => 'Tata Kelola TI',
                'foto' => 'https://images.unsplash.com/photo-1720501828093-c792c10e3f0b?auto=format&fit=crop&crop=faces&w=400&h=400&q=80',
                'email' => 'yusuf.hidayat@unusa.ac.id',
                'mata_kuliah' => 'Tata Kelola TI, Audit Sistem Informasi, Keamanan Sistem Informasi',
                'profil' => 'Spesialis tata kelola teknologi informasi, manajemen risiko TI, dan audit sistem informasi perusahaan.',
                'sort_order' => 3,
            ],
            [
                'code' => 'D-04',
                'nama' => 'Siti Aminah, S.Kom., M.Kom',
                'nidn' => '0715029004',
                'jabatan' => 'Asisten Ahli',
                'pendidikan' => 'S2',
                'bidang' => 'Rekayasa Perangkat Lunak',
                'foto' => 'https://images.unsplash.com/photo-1750231413225-6513a6622a2a?auto=format&fit=crop&crop=faces&w=400&h=400&q=80',
                'email' => 'siti.aminah@unusa.ac.id',
                'mata_kuliah' => 'Rekayasa Perangkat Lunak, Pemrograman Web, Manajemen Proyek SI',
                'profil' => 'Mengampu pengembangan perangkat lunak modern dan praktik manajemen proyek sistem informasi.',
                'sort_order' => 4,
            ],
            [
                'code' => 'D-05',
                'nama' => 'Fitri Handayani, M.Sc',
                'nidn' => '0709119105',
                'jabatan' => 'Asisten Ahli',
                'pendidikan' => 'S2',
                'bidang' => 'Analitika Data',
                'foto' => 'https://images.unsplash.com/photo-1611682060567-d7b07878d380?auto=format&fit=crop&crop=faces&w=400&h=400&q=80',
                'email' => 'fitri.handayani@unusa.ac.id',
                'mata_kuliah' => 'Statistika, Data Warehouse, Business Intelligence',
                'profil' => 'Mendalami visualisasi data interaktif, business intelligence, dan pemodelan statistik terapan.',
                'sort_order' => 5,
            ],
            [
                'code' => 'D-06',
                'nama' => 'Ahmad Zainuri, M.Kom',
                'nidn' => '0728058706',
                'jabatan' => 'Lektor',
                'pendidikan' => 'S2',
                'bidang' => 'Basis Data',
                'foto' => 'https://images.unsplash.com/photo-1681097561932-36d0df02b379?auto=format&fit=crop&crop=faces&w=400&h=400&q=80',
                'email' => 'ahmad.zainuri@unusa.ac.id',
                'mata_kuliah' => 'Basis Data, Struktur Data, Algoritma dan Pemrograman',
                'profil' => 'Aktif meneliti arsitektur basis data skala besar dan optimasi kueri untuk aplikasi enterprise.',
                'sort_order' => 6,
            ],
            [
                'code' => 'D-07',
                'nama' => 'Dr. Laila Nurhayati, M.M',
                'nidn' => '0711068407',
                'jabatan' => 'Lektor Kepala',
                'pendidikan' => 'S3',
                'bidang' => 'Sistem Informasi Manajemen',
                'foto' => 'https://images.unsplash.com/photo-1690166444476-8cc4c0f24032?auto=format&fit=crop&crop=faces&w=400&h=400&q=80',
                'email' => 'laila.nurhayati@unusa.ac.id',
                'mata_kuliah' => 'Enterprise Resource Planning, Pengantar Sistem Informasi',
                'profil' => 'Mengajar dan membimbing riset strategi sistem informasi bisnis dan implementasi ERP pada UMKM.',
                'sort_order' => 7,
            ],
            [
                'code' => 'D-08',
                'nama' => 'Bagus Prasetyo, M.Kom',
                'nidn' => '0730049208',
                'jabatan' => 'Tenaga Pengajar',
                'pendidikan' => 'S2',
                'bidang' => 'UI/UX Design',
                'foto' => 'https://images.unsplash.com/photo-1543132220-3ec99c6094dc?auto=format&fit=crop&crop=faces&w=400&h=400&q=80',
                'email' => 'bagus.prasetyo@unusa.ac.id',
                'mata_kuliah' => 'UI/UX Design, Interaksi Manusia dan Komputer',
                'profil' => 'Praktisi dan pembina tim mahasiswa bidang desain pengalaman pengguna dan aksesibilitas digital.',
                'sort_order' => 8,
            ],
        ];

        foreach ($items as $item) {
            Lecturer::create($item);
        }
    }

    protected function seedAchievements(): void
    {
        $items = [
            [
                'code' => 'P-01',
                'judul' => 'Gemastik — Desain Pengalaman Pengguna',
                'nama' => 'Nabila Putri dkk.',
                'tingkat' => 'Nasional',
                'peringkat' => 'Juara 1',
                'tahun' => 2025,
                'foto' => 'https://images.unsplash.com/photo-1576267423048-15c0040fec78?auto=format&fit=crop&w=800&h=500&q=80',
            ],
            [
                'code' => 'P-02',
                'judul' => 'Hackathon Smart City Jawa Timur',
                'nama' => 'Fajar Hidayat dkk.',
                'tingkat' => 'Regional',
                'peringkat' => 'Juara 2',
                'tahun' => 2025,
                'foto' => 'https://images.unsplash.com/photo-1695067058684-da5a90013c57?auto=format&fit=crop&w=800&h=500&q=80',
            ],
            [
                'code' => 'P-03',
                'judul' => 'ASEAN Data Science Explorers',
                'nama' => 'Tim Data UNUSA',
                'tingkat' => 'Internasional',
                'peringkat' => 'Finalis',
                'tahun' => 2025,
                'foto' => 'https://images.unsplash.com/photo-1725272333872-4c2d274ad493?auto=format&fit=crop&w=800&h=500&q=80',
            ],
            [
                'code' => 'P-04',
                'judul' => 'Business Plan Competition Nasional',
                'nama' => 'Laila Rahma dkk.',
                'tingkat' => 'Nasional',
                'peringkat' => 'Juara 3',
                'tahun' => 2024,
                'foto' => 'https://images.unsplash.com/photo-1749723477883-4e3dde4e3a28?auto=format&fit=crop&w=800&h=500&q=80',
            ],
        ];

        foreach ($items as $item) {
            Achievement::create($item);
        }
    }

    protected function seedNews(): void
    {
        $items = [
            [
                'code' => 'b1',
                'badge' => 'Prestasi Mahasiswa',
                'corner' => 'Tingkat Nasional',
                'date_label' => '28 Februari 2025',
                'read_minutes' => 4,
                'title' => 'Tim mahasiswa raih juara pertama kompetisi UI/UX tingkat nasional 2025',
                'excerpt' => 'Inovasi prototipe aplikasi aksesibilitas data bagi penyandang disabilitas mendapat apresiasi juri.',
                'author' => 'Himpunan Mahasiswa',
                'author_initials' => 'HM',
                'image_url' => 'https://images.unsplash.com/photo-1523580494863-6f3031224c94?w=1200&q=80',
                'body' => [
                    'Tim mahasiswa Program Studi Sistem Informasi UNUSA berhasil meraih juara pertama pada kompetisi desain UI/UX tingkat nasional 2025. Tim yang terdiri dari tiga mahasiswa angkatan 2022 ini mengusung prototipe aplikasi yang membantu penyandang disabilitas netra mengakses data publik.',
                    'Dewan juri menilai karya tersebut unggul dari sisi riset pengguna, konsistensi desain, dan dampak sosial. Prototipe dikembangkan selama tiga bulan dengan melibatkan komunitas disabilitas di Surabaya sebagai responden pengujian.',
                    'Ketua Program Studi menyampaikan bahwa capaian ini menjadi bukti kurikulum berbasis proyek mampu mendorong mahasiswa menghasilkan karya yang relevan dengan kebutuhan masyarakat.',
                ],
            ],
            [
                'code' => 'b2',
                'badge' => 'Publikasi Dosen',
                'corner' => 'Scopus Q1',
                'date_label' => '20 Februari 2025',
                'read_minutes' => 6,
                'title' => "Dosen terbitkan riset 'Big Data Analytics Retensi Mahasiswa' di jurnal bereputasi",
                'excerpt' => 'Karya ilmiah ini membedah algoritma prediktif berbasis machine learning untuk memetakan risiko studi.',
                'author' => 'Lab Riset Data',
                'author_initials' => 'LR',
                'image_url' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&q=80',
                'body' => [
                    'Dosen Program Studi Sistem Informasi menerbitkan artikel ilmiah pada jurnal internasional bereputasi terindeks Scopus Q1. Penelitian ini memanfaatkan data akademik anonim untuk memprediksi risiko mahasiswa tidak melanjutkan studi.',
                    'Model yang dikembangkan mencapai akurasi di atas 88% dan kini diuji coba sebagai sistem peringatan dini bagi dosen pembimbing akademik.',
                    'Publikasi ini menambah daftar luaran riset prodi dan menjadi bagian dari roadmap penelitian kelompok keahlian Sains Data.',
                ],
            ],
            [
                'code' => 'b3',
                'badge' => 'Pengabdian',
                'corner' => 'Dampak Wilayah',
                'date_label' => '15 Februari 2025',
                'read_minutes' => 5,
                'title' => 'Pelatihan tata kelola data UMKM berbasis cloud bersama desa binaan',
                'excerpt' => 'Puluhan pelaku usaha lokal dibantu menstandarkan pencatatan inventaris dan penjualan digital.',
                'author' => 'Tim Pengabdian',
                'author_initials' => 'TP',
                'image_url' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=1200&q=80',
                'body' => [
                    'Tim pengabdian masyarakat dosen dan mahasiswa Sistem Informasi menggelar pelatihan tata kelola data bagi pelaku UMKM di desa binaan. Peserta diajak memindahkan pencatatan manual ke aplikasi spreadsheet berbasis cloud.',
                    'Selain pelatihan, tim juga menyediakan pendampingan selama satu bulan agar peserta dapat menerapkan pencatatan secara konsisten.',
                    'Kegiatan ini merupakan bagian dari program pengabdian tahunan prodi yang berfokus pada literasi digital masyarakat.',
                ],
            ],
            [
                'code' => 'b4',
                'badge' => 'Akademik',
                'corner' => 'Akreditasi',
                'date_label' => '3 Februari 2025',
                'read_minutes' => 3,
                'title' => 'Prodi Sistem Informasi raih akreditasi Unggul dari LAM INFOKOM',
                'excerpt' => 'Capaian ini menegaskan mutu tata kelola, kurikulum, dan luaran tridharma prodi.',
                'author' => 'Gugus Penjaminan Mutu',
                'author_initials' => 'GM',
                'image_url' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=1200&q=80',
                'body' => [
                    'Program Studi Sistem Informasi UNUSA resmi memperoleh status akreditasi Unggul dari LAM INFOKOM. Penilaian mencakup tata kelola, kurikulum, sumber daya, hingga luaran penelitian dan pengabdian.',
                    'Status ini berlaku selama lima tahun dan menjadi landasan prodi untuk memperluas kerja sama nasional maupun internasional.',
                ],
            ],
            [
                'code' => 'b5',
                'badge' => 'Kegiatan',
                'corner' => 'Workshop',
                'date_label' => '27 Januari 2025',
                'read_minutes' => 4,
                'title' => 'Workshop analitik data dengan Python untuk mahasiswa baru',
                'excerpt' => 'Lebih dari 150 mahasiswa mengikuti workshop pengenalan analisis data menggunakan Python.',
                'author' => 'Himpunan Mahasiswa',
                'author_initials' => 'HM',
                'image_url' => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=1200&q=80',
                'body' => [
                    'Himpunan Mahasiswa Sistem Informasi menyelenggarakan workshop analitik data dengan Python bagi mahasiswa baru. Materi meliputi dasar pemrograman, pengolahan data dengan pandas, hingga visualisasi sederhana.',
                    'Kegiatan ini diharapkan membekali mahasiswa sejak awal perkuliahan dengan keterampilan yang dibutuhkan industri.',
                ],
            ],
            [
                'code' => 'b6',
                'badge' => 'Prestasi Mahasiswa',
                'corner' => 'Internasional',
                'date_label' => '12 Januari 2025',
                'read_minutes' => 4,
                'title' => 'Mahasiswa SI lolos program pertukaran pelajar ke Malaysia',
                'excerpt' => 'Empat mahasiswa akan mengikuti perkuliahan satu semester di universitas mitra.',
                'author' => 'Kantor Urusan Internasional',
                'author_initials' => 'KI',
                'image_url' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=1200&q=80',
                'body' => [
                    'Empat mahasiswa Sistem Informasi dinyatakan lolos seleksi program pertukaran pelajar ke universitas mitra di Malaysia. Selama satu semester, mereka akan mengikuti perkuliahan dan proyek kolaboratif.',
                    'Mata kuliah yang diambil akan dikonversi ke dalam kurikulum prodi melalui skema rekognisi kredit.',
                ],
            ],
        ];

        foreach (array_values($items) as $idx => $item) {
            $ts = now()->subDays($idx + 1);
            $article = new NewsArticle($item);
            $article->created_at = $ts;
            $article->updated_at = $ts;
            $article->save();
        }
    }

    protected function seedCourses(): void
    {
        $items = [
            ['code' => 'MK-SI101', 'tahun' => 2024, 'semester' => 1, 'kode' => 'SI101', 'nama' => 'Pengantar Sistem Informasi', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI102', 'tahun' => 2024, 'semester' => 1, 'kode' => 'SI102', 'nama' => 'Algoritma dan Pemrograman', 'sks' => 4, 'pilihan' => false],
            ['code' => 'MK-SI103', 'tahun' => 2024, 'semester' => 1, 'kode' => 'SI103', 'nama' => 'Matematika Diskrit', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI104', 'tahun' => 2024, 'semester' => 1, 'kode' => 'SI104', 'nama' => 'Pendidikan Aswaja', 'sks' => 2, 'pilihan' => false],
            ['code' => 'MK-SI201', 'tahun' => 2024, 'semester' => 2, 'kode' => 'SI201', 'nama' => 'Struktur Data', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI202', 'tahun' => 2024, 'semester' => 2, 'kode' => 'SI202', 'nama' => 'Basis Data', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI203', 'tahun' => 2024, 'semester' => 2, 'kode' => 'SI203', 'nama' => 'Statistika', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI204', 'tahun' => 2024, 'semester' => 2, 'kode' => 'SI204', 'nama' => 'Proses Bisnis', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI301', 'tahun' => 2024, 'semester' => 3, 'kode' => 'SI301', 'nama' => 'Pemrograman Web', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI302', 'tahun' => 2024, 'semester' => 3, 'kode' => 'SI302', 'nama' => 'Analisis dan Perancangan Sistem', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI303', 'tahun' => 2024, 'semester' => 3, 'kode' => 'SI303', 'nama' => 'Jaringan Komputer', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI304', 'tahun' => 2024, 'semester' => 3, 'kode' => 'SI304', 'nama' => 'Sistem Informasi Manajemen', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI401', 'tahun' => 2024, 'semester' => 4, 'kode' => 'SI401', 'nama' => 'Rekayasa Perangkat Lunak', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI402', 'tahun' => 2024, 'semester' => 4, 'kode' => 'SI402', 'nama' => 'Interaksi Manusia dan Komputer', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI403', 'tahun' => 2024, 'semester' => 4, 'kode' => 'SI403', 'nama' => 'Manajemen Proyek SI', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI404', 'tahun' => 2024, 'semester' => 4, 'kode' => 'SI404', 'nama' => 'Data Warehouse', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI501', 'tahun' => 2024, 'semester' => 5, 'kode' => 'SI501', 'nama' => 'Tata Kelola TI', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI502', 'tahun' => 2024, 'semester' => 5, 'kode' => 'SI502', 'nama' => 'Analitika Data', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI503', 'tahun' => 2024, 'semester' => 5, 'kode' => 'SI503', 'nama' => 'Enterprise Resource Planning', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI551', 'tahun' => 2024, 'semester' => 5, 'kode' => 'SI551', 'nama' => 'Machine Learning', 'sks' => 3, 'pilihan' => true],
            ['code' => 'MK-SI601', 'tahun' => 2024, 'semester' => 6, 'kode' => 'SI601', 'nama' => 'Audit Sistem Informasi', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI602', 'tahun' => 2024, 'semester' => 6, 'kode' => 'SI602', 'nama' => 'Keamanan Sistem Informasi', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI603', 'tahun' => 2024, 'semester' => 6, 'kode' => 'SI603', 'nama' => 'Metodologi Penelitian', 'sks' => 2, 'pilihan' => false],
            ['code' => 'MK-SI651', 'tahun' => 2024, 'semester' => 6, 'kode' => 'SI651', 'nama' => 'UI/UX Design', 'sks' => 3, 'pilihan' => true],
            ['code' => 'MK-SI701', 'tahun' => 2024, 'semester' => 7, 'kode' => 'SI701', 'nama' => 'Kerja Praktik', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI702', 'tahun' => 2024, 'semester' => 7, 'kode' => 'SI702', 'nama' => 'Kuliah Kerja Nyata', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI751', 'tahun' => 2024, 'semester' => 7, 'kode' => 'SI751', 'nama' => 'E-Government', 'sks' => 3, 'pilihan' => true],
            ['code' => 'MK-SI752', 'tahun' => 2024, 'semester' => 7, 'kode' => 'SI752', 'nama' => 'Business Intelligence', 'sks' => 3, 'pilihan' => true],
            ['code' => 'MK-SI801', 'tahun' => 2024, 'semester' => 8, 'kode' => 'SI801', 'nama' => 'Skripsi', 'sks' => 6, 'pilihan' => false],
        ];

        foreach ($items as $item) {
            Course::create($item);
        }
    }
}
