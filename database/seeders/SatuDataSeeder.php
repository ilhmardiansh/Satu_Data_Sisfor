<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Category;
use App\Models\Course;
use App\Models\DataForm;
use App\Models\DataRequest;
use App\Models\Dataset;
use App\Models\FormSubmission;
use App\Models\Lecturer;
use App\Models\NewsArticle;
use App\Models\Permission;
use App\Models\ProgramProfile;
use App\Models\Role;
use App\Models\SubmissionValue;
use App\Models\User;
use App\Models\Visualization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SatuDataSeeder extends Seeder
{
    public function run(?int $preserveUserId = null): void
    {
        DB::transaction(function () use ($preserveUserId): void {
            $this->clearTables($preserveUserId);

            $roles = $this->seedRolesAndPermissions();
            $users = $this->seedUsers($roles, $preserveUserId);
            $this->seedCategories();
            $this->seedProgramProfile($users['U-01']);
            $this->seedLecturers($users['U-01']);
            $this->seedAchievements($users['U-01']);
            $this->seedNews($users['U-01']);
            $this->seedCourses($users['U-01']);
        });
    }

    protected function clearTables(?int $preserveUserId = null): void
    {
        Visualization::query()->delete();
        DataRequest::query()->delete();
        SubmissionValue::query()->delete();
        FormSubmission::query()->delete();
        Dataset::query()->delete();
        DataForm::query()->delete();
        Category::query()->delete();
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
     * Seed peran, hak_akses, and peran_hak_akses pivot per DPPL RBAC specification.
     *
     * @return array<string, Role>
     */
    protected function seedRolesAndPermissions(): array
    {
        $permissionNames = [
            'lihat_ringkasan',
            'isi_formulir',
            'lihat_riwayat',
            'kelola_formulir',
            'lihat_data_terkumpul',
            'preprocessing_data',
            'bersihkan_data',
            'impor_data',
            'kelola_kategori',
            'kelola_dataset',
            'kelola_visualisasi',
            'konfirmasi_permohonan',
            'kelola_pengguna',
            'kelola_konten',
        ];

        $permIds = [];
        foreach ($permissionNames as $name) {
            $perm = Permission::query()->firstOrCreate(['nama_hak_akses' => $name]);
            $permIds[$name] = $perm->id;
        }

        $roleSpecs = [
            'admin' => [
                'deskripsi' => 'Admin Program Studi Sistem Informasi (akses penuh tata kelola sistem, formulir, pre-processing, pengguna, dan konten portal)',
                'permissions' => $permissionNames,
            ],
            'wali' => [
                'deskripsi' => 'Wali Data Program Studi (mengelola instrumen formulir, pre-processing data, penyusunan dataset, visualisasi, dan permohonan data)',
                'permissions' => [
                    'lihat_ringkasan',
                    'lihat_riwayat',
                    'kelola_formulir',
                    'lihat_data_terkumpul',
                    'preprocessing_data',
                    'bersihkan_data',
                    'impor_data',
                    'kelola_kategori',
                    'kelola_dataset',
                    'kelola_visualisasi',
                    'konfirmasi_permohonan',
                ],
            ],
            'produsen' => [
                'deskripsi' => 'Produsen Data (Dosen dan Mahasiswa pengisi formulir pengumpulan data)',
                'permissions' => [
                    'lihat_ringkasan',
                    'isi_formulir',
                    'lihat_riwayat',
                ],
            ],
        ];

        $roles = [];
        foreach ($roleSpecs as $roleName => $spec) {
            $role = Role::query()->updateOrCreate(
                ['nama_peran' => $roleName],
                ['deskripsi' => $spec['deskripsi']]
            );
            $role->aturHakAkses(array_map(fn (string $p): int => $permIds[$p], $spec['permissions']));
            $roles[$roleName] = $role;
        }

        return $roles;
    }

    /**
     * @param  array<string, Role>  $roles
     * @return array<string, User>
     */
    protected function seedUsers(array $roles, ?int $preserveUserId = null): array
    {
        $seedUsers = [
            [
                'code' => 'U-01',
                'nama_pengguna' => 'Administrator Prodi',
                'nim' => 'admin',
                'email' => 'admin.si@unusa.ac.id',
                'password' => 'admin123',
                'role' => 'admin',
                'jenis' => null,
                'aktif' => true,
            ],
            [
                'code' => 'U-02',
                'nama_pengguna' => 'Rina Wulandari, M.Kom',
                'nim' => 'walidata',
                'email' => 'walidata.si@unusa.ac.id',
                'password' => 'wali123',
                'role' => 'wali',
                'jenis' => null,
                'aktif' => true,
            ],
            [
                'code' => 'U-03',
                'nama_pengguna' => 'Nabila Putri',
                'nim' => '1462200031',
                'email' => '1462200031@student.unusa.ac.id',
                'password' => 'mhs123',
                'role' => 'produsen',
                'jenis' => 'Mahasiswa',
                'aktif' => true,
            ],
            [
                'code' => 'U-04',
                'nama_pengguna' => 'Fajar Hidayat',
                'nim' => '1462300012',
                'email' => '1462300012@student.unusa.ac.id',
                'password' => 'mhs123',
                'role' => 'produsen',
                'jenis' => 'Mahasiswa',
                'aktif' => true,
            ],
            [
                'code' => 'U-05',
                'nama_pengguna' => 'Dr. Andi Setiawan, M.Kom',
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
            $roleId = $roles[$item['role']]->id;

            if ($preservedUser && $preservedUser->nim === $item['nim']) {
                $preservedUser->update([
                    'code' => $item['code'],
                    'nama_pengguna' => $item['nama_pengguna'],
                    'email' => $item['email'],
                    'id_peran' => $roleId,
                    'jenis' => $item['jenis'],
                    'aktif' => $item['aktif'],
                ]);
                $preservedUser->load('peran.hakAkses');
                $map[$item['code']] = $preservedUser;
                continue;
            }

            $user = User::create([
                'code' => $item['code'],
                'nama_pengguna' => $item['nama_pengguna'],
                'nim' => $item['nim'],
                'email' => $item['email'],
                'kata_sandi' => Hash::make($item['password']),
                'id_peran' => $roleId,
                'jenis' => $item['jenis'],
                'aktif' => $item['aktif'],
            ]);
            $user->load('peran.hakAkses');
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
            ['code' => 'KT-MHS-05', 'nama_kategori' => 'Beasiswa', 'deskripsi' => 'Penerima dan sebaran dukungan finansial mahasiswa.'],
            ['code' => 'KT-MHS-04', 'nama_kategori' => 'Prestasi & Rekognisi', 'deskripsi' => 'Capaian prestasi dan rekognisi mahasiswa.'],
            ['code' => 'KT-TRD-03', 'nama_kategori' => 'Pengabdian Masyarakat', 'deskripsi' => 'Kegiatan pengabdian yang diinisiasi oleh prodi.'],
            ['code' => 'KT-TRD-02', 'nama_kategori' => 'Penelitian', 'deskripsi' => 'Penelitian dosen dan mahasiswa prodi.'],
            ['code' => 'KT-MHS-01', 'nama_kategori' => 'Kemahasiswaan', 'deskripsi' => 'Profil, status, dan keaktifan mahasiswa.'],
            ['code' => 'KT-AKD-01', 'nama_kategori' => 'Akademik', 'deskripsi' => 'Perkuliahan, IPK, dan kelulusan.'],
            ['code' => 'KT-MHS-06', 'nama_kategori' => 'Alumni', 'deskripsi' => 'Tracer study dan keterserapan lulusan.'],
            ['code' => 'KT-SDM-01', 'nama_kategori' => 'SDM', 'deskripsi' => 'Dosen dan tenaga kependidikan.'],
            ['code' => 'KT-TRD-04', 'nama_kategori' => 'Publikasi Ilmiah', 'deskripsi' => 'Jurnal, prosiding, dan HKI.'],
            ['code' => 'KT-KS-01', 'nama_kategori' => 'Kerjasama', 'deskripsi' => 'Mitra dan dokumen kerja sama.'],
            ['code' => 'KT-MTU-01', 'nama_kategori' => 'Akreditasi', 'deskripsi' => 'Dokumen dan capaian akreditasi.'],
            ['code' => 'KT-KEU-01', 'nama_kategori' => 'Keuangan', 'deskripsi' => 'Anggaran dan realisasi prodi.'],
            ['code' => 'KT-AMI-01', 'nama_kategori' => 'Audit Mutu Internal (AMI)', 'deskripsi' => 'Instrumen SNPT-U 28 & 38 standar, benchmark UNUSA, Permintaan Tindakan Koreksi (PTK), dan saran pengembangan Prodi S1 Sistem Informasi.'],
        ];

        $map = [];
        foreach ($items as $item) {
            $map[$item['code']] = Category::create($item);
        }

        return $map;
    }

    protected function seedProgramProfile(User $manager): void
    {
        ProgramProfile::create([
            'deskripsi_prodi' => 'Program Studi Sistem Informasi Universitas Nahdlatul Ulama Surabaya menyiapkan lulusan yang mampu merancang, membangun, dan mengelola sistem informasi untuk mendukung proses bisnis organisasi dengan berlandaskan nilai-nilai Ahlussunnah wal Jamaah.',
            'visi_misi' => [
                'visi' => 'Menjadi program studi Sistem Informasi yang unggul dalam pengembangan sistem informasi bisnis dan tata kelola data berbasis nilai Islam Ahlussunnah wal Jamaah di tingkat nasional pada tahun 2030.',
                'misi' => [
                    'Menyelenggarakan pendidikan sistem informasi yang berkualitas dan relevan dengan kebutuhan industri.',
                    'Melaksanakan penelitian di bidang sistem informasi, analitika data, dan transformasi digital.',
                    'Melaksanakan pengabdian kepada masyarakat melalui penerapan teknologi informasi tepat guna.',
                    'Menjalin kerja sama dengan pemerintah, industri, dan perguruan tinggi di dalam maupun luar negeri.',
                ],
            ],
            'akreditasi' => 'Baik Sekali',
            'tahun_berdiri' => '2013',
            'lokasi_kampus' => 'Kampus B UNUSA, Jl. Jemursari No. 51-57, Surabaya, Jawa Timur 60237',
            'kontak' => [
                'email' => 'si@unusa.ac.id',
                'telp' => '(031) 8291920',
            ],
            'sejarah' => "Program Studi Sistem Informasi Universitas Nahdlatul Ulama Surabaya (UNUSA) didirikan pada tahun 2013 untuk menjawab kebutuhan tenaga profesional di bidang teknologi informasi, analitika data, dan manajemen sistem bisnis di Jawa Timur dan Indonesia.\n\nSejak awal pendiriannya, program studi ini terus mengembangkan kurikulum berbasis proyek serta tata kelola data terintegrasi yang memadukan kompetensi teknologi informasi dengan nilai-nilai Islam Ahlussunnah wal Jamaah.",
            'nama_prodi' => 'S1 Sistem Informasi',
            'gelar' => 'Sarjana Komputer (S.Kom)',
            'durasi_studi' => '8 Semester (4 Tahun)',
            'persyaratan' => 'Lulusan SMA/SMK/MA atau sederajat yang memenuhi syarat seleksi penerimaan mahasiswa baru UNUSA.',
            'fakultas' => 'Fakultas Ekonomi Bisnis dan Teknologi Digital',
            'fasilitas' => 'Laboratorium Rekayasa Perangkat Lunak, Lab Analitika Data & AI, Smart Classroom, Perpustakaan Terpadu, serta Ruang Inkubasi Bisnis Digital.',
            'dikelola_oleh' => $manager->id,
        ]);
    }

    protected function seedLecturers(User $manager): void
    {
        $items = [
            [
                'code' => 'D-01',
                'nama' => 'Dr. Andi Setiawan, M.Kom',
                'nidn' => '0712048601',
                'jabatan' => 'Lektor Kepala',
                'pendidikan' => 'S3',
                'bidang' => 'Machine Learning',
                'jalur_foto' => 'https://images.unsplash.com/photo-1600896997793-b8ed3459a17f?auto=format&fit=crop&crop=faces&w=400&h=400&q=80',
                'email' => 'andi.setiawan@unusa.ac.id',
                'mata_kuliah' => 'Machine Learning, Analitika Data, Metodologi Penelitian',
                'profil' => 'Peneliti dan dosen senior bidang Machine Learning terapan serta analitika data pendidikan tinggi.',
                'urutan' => 1,
            ],
            [
                'code' => 'D-02',
                'nama' => 'Rina Wulandari, M.Kom',
                'nidn' => '0703108802',
                'jabatan' => 'Lektor',
                'pendidikan' => 'S2',
                'bidang' => 'E-Government',
                'jalur_foto' => 'https://images.unsplash.com/photo-1585917170055-e40d30c73414?auto=format&fit=crop&crop=faces&w=400&h=400&q=80',
                'email' => 'rina.wulandari@unusa.ac.id',
                'mata_kuliah' => 'E-Government, Proses Bisnis, Sistem Informasi Manajemen',
                'profil' => 'Berfokus pada transformasi digital pemerintahan daerah dan adopsi layanan publik berbasis data.',
                'urutan' => 2,
            ],
            [
                'code' => 'D-03',
                'nama' => 'Dr. Moch. Yusuf Hidayat, M.T',
                'nidn' => '0721078503',
                'jabatan' => 'Lektor',
                'pendidikan' => 'S3',
                'bidang' => 'Tata Kelola TI',
                'jalur_foto' => 'https://images.unsplash.com/photo-1720501828093-c792c10e3f0b?auto=format&fit=crop&crop=faces&w=400&h=400&q=80',
                'email' => 'yusuf.hidayat@unusa.ac.id',
                'mata_kuliah' => 'Tata Kelola TI, Audit Sistem Informasi, Keamanan Sistem Informasi',
                'profil' => 'Spesialis tata kelola teknologi informasi, manajemen risiko TI, dan audit sistem informasi perusahaan.',
                'urutan' => 3,
            ],
            [
                'code' => 'D-04',
                'nama' => 'Siti Aminah, S.Kom., M.Kom',
                'nidn' => '0715029004',
                'jabatan' => 'Asisten Ahli',
                'pendidikan' => 'S2',
                'bidang' => 'Rekayasa Perangkat Lunak',
                'jalur_foto' => 'https://images.unsplash.com/photo-1750231413225-6513a6622a2a?auto=format&fit=crop&crop=faces&w=400&h=400&q=80',
                'email' => 'siti.aminah@unusa.ac.id',
                'mata_kuliah' => 'Rekayasa Perangkat Lunak, Pemrograman Web, Manajemen Proyek SI',
                'profil' => 'Mengampu pengembangan perangkat lunak modern dan praktik manajemen proyek sistem informasi.',
                'urutan' => 4,
            ],
            [
                'code' => 'D-05',
                'nama' => 'Fitri Handayani, M.Sc',
                'nidn' => '0709119105',
                'jabatan' => 'Asisten Ahli',
                'pendidikan' => 'S2',
                'bidang' => 'Analitika Data',
                'jalur_foto' => 'https://images.unsplash.com/photo-1611682060567-d7b07878d380?auto=format&fit=crop&crop=faces&w=400&h=400&q=80',
                'email' => 'fitri.handayani@unusa.ac.id',
                'mata_kuliah' => 'Statistika, Data Warehouse, Business Intelligence',
                'profil' => 'Mendalami visualisasi data interaktif, business intelligence, dan pemodelan statistik terapan.',
                'urutan' => 5,
            ],
            [
                'code' => 'D-06',
                'nama' => 'Ahmad Zainuri, M.Kom',
                'nidn' => '0728058706',
                'jabatan' => 'Lektor',
                'pendidikan' => 'S2',
                'bidang' => 'Basis Data',
                'jalur_foto' => 'https://images.unsplash.com/photo-1681097561932-36d0df02b379?auto=format&fit=crop&crop=faces&w=400&h=400&q=80',
                'email' => 'ahmad.zainuri@unusa.ac.id',
                'mata_kuliah' => 'Basis Data, Struktur Data, Algoritma dan Pemrograman',
                'profil' => 'Aktif meneliti arsitektur basis data skala besar dan optimasi kueri untuk aplikasi enterprise.',
                'urutan' => 6,
            ],
            [
                'code' => 'D-07',
                'nama' => 'Dr. Laila Nurhayati, M.M',
                'nidn' => '0711068407',
                'jabatan' => 'Lektor Kepala',
                'pendidikan' => 'S3',
                'bidang' => 'Sistem Informasi Manajemen',
                'jalur_foto' => 'https://images.unsplash.com/photo-1690166444476-8cc4c0f24032?auto=format&fit=crop&crop=faces&w=400&h=400&q=80',
                'email' => 'laila.nurhayati@unusa.ac.id',
                'mata_kuliah' => 'Enterprise Resource Planning, Pengantar Sistem Informasi',
                'profil' => 'Mengajar dan membimbing riset strategi sistem informasi bisnis dan implementasi ERP pada UMKM.',
                'urutan' => 7,
            ],
            [
                'code' => 'D-08',
                'nama' => 'Bagus Prasetyo, M.Kom',
                'nidn' => '0730049208',
                'jabatan' => 'Tenaga Pengajar',
                'pendidikan' => 'S2',
                'bidang' => 'UI/UX Design',
                'jalur_foto' => 'https://images.unsplash.com/photo-1543132220-3ec99c6094dc?auto=format&fit=crop&crop=faces&w=400&h=400&q=80',
                'email' => 'bagus.prasetyo@unusa.ac.id',
                'mata_kuliah' => 'UI/UX Design, Interaksi Manusia dan Komputer',
                'profil' => 'Praktisi dan pembina tim mahasiswa bidang desain pengalaman pengguna dan aksesibilitas digital.',
                'urutan' => 8,
            ],
        ];

        foreach ($items as $item) {
            Lecturer::create([
                ...$item,
                'dikelola_oleh' => $manager->id,
            ]);
        }
    }

    protected function seedAchievements(User $manager): void
    {
        $items = [
            [
                'code' => 'P-01',
                'judul' => 'Gemastik: Desain Pengalaman Pengguna',
                'nama_peraih' => 'Nabila Putri dkk.',
                'tingkat' => 'Nasional',
                'peringkat' => 'Juara 1',
                'tahun' => 2025,
                'jalur_gambar' => 'https://images.unsplash.com/photo-1576267423048-15c0040fec78?auto=format&fit=crop&w=800&h=500&q=80',
            ],
            [
                'code' => 'P-02',
                'judul' => 'Hackathon Smart City Jawa Timur',
                'nama_peraih' => 'Fajar Hidayat dkk.',
                'tingkat' => 'Regional',
                'peringkat' => 'Juara 2',
                'tahun' => 2025,
                'jalur_gambar' => 'https://images.unsplash.com/photo-1695067058684-da5a90013c57?auto=format&fit=crop&w=800&h=500&q=80',
            ],
            [
                'code' => 'P-03',
                'judul' => 'ASEAN Data Science Explorers',
                'nama_peraih' => 'Tim Data UNUSA',
                'tingkat' => 'Internasional',
                'peringkat' => 'Finalis',
                'tahun' => 2025,
                'jalur_gambar' => 'https://images.unsplash.com/photo-1725272333872-4c2d274ad493?auto=format&fit=crop&w=800&h=500&q=80',
            ],
            [
                'code' => 'P-04',
                'judul' => 'Business Plan Competition Nasional',
                'nama_peraih' => 'Laila Rahma dkk.',
                'tingkat' => 'Nasional',
                'peringkat' => 'Juara 3',
                'tahun' => 2024,
                'jalur_gambar' => 'https://images.unsplash.com/photo-1749723477883-4e3dde4e3a28?auto=format&fit=crop&w=800&h=500&q=80',
            ],
        ];

        foreach ($items as $item) {
            Achievement::create([
                ...$item,
                'dikelola_oleh' => $manager->id,
            ]);
        }
    }

    protected function seedNews(User $manager): void
    {
        $items = [
            [
                'code' => 'b1',
                'kategori_badge' => 'Prestasi Mahasiswa',
                'label_pojok' => 'Tingkat Nasional',
                'label_tanggal' => '28 Februari 2025',
                'tanggal_publikasi' => '2025-02-28',
                'menit_baca' => 4,
                'judul' => 'Tim mahasiswa raih juara pertama kompetisi UI/UX tingkat nasional 2025',
                'ringkasan' => 'Inovasi prototipe aplikasi aksesibilitas data bagi penyandang disabilitas mendapat apresiasi juri.',
                'penulis' => 'Himpunan Mahasiswa',
                'inisial_penulis' => 'HM',
                'jalur_gambar' => 'https://images.unsplash.com/photo-1523580494863-6f3031224c94?w=1200&q=80',
                'isi_berita' => [
                    'Tim mahasiswa Program Studi Sistem Informasi UNUSA berhasil meraih juara pertama pada kompetisi desain UI/UX tingkat nasional 2025. Tim yang terdiri dari tiga mahasiswa angkatan 2022 ini mengusung prototipe aplikasi yang membantu penyandang disabilitas netra mengakses data publik.',
                    'Dewan juri menilai karya tersebut unggul dari sisi riset pengguna, konsistensi desain, dan dampak sosial. Prototipe dikembangkan selama tiga bulan dengan melibatkan komunitas disabilitas di Surabaya sebagai responden pengujian.',
                    'Ketua Program Studi menyampaikan bahwa capaian ini menjadi bukti kurikulum berbasis proyek mampu mendorong mahasiswa menghasilkan karya yang relevan dengan kebutuhan masyarakat.',
                ],
            ],
            [
                'code' => 'b2',
                'kategori_badge' => 'Publikasi Dosen',
                'label_pojok' => 'Scopus Q1',
                'label_tanggal' => '20 Februari 2025',
                'tanggal_publikasi' => '2025-02-20',
                'menit_baca' => 6,
                'judul' => "Dosen terbitkan riset 'Big Data Analytics Retensi Mahasiswa' di jurnal bereputasi",
                'ringkasan' => 'Karya ilmiah ini membedah algoritma prediktif berbasis machine learning untuk memetakan risiko studi.',
                'penulis' => 'Lab Riset Data',
                'inisial_penulis' => 'LR',
                'jalur_gambar' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&q=80',
                'isi_berita' => [
                    'Dosen Program Studi Sistem Informasi menerbitkan artikel ilmiah pada jurnal internasional bereputasi terindeks Scopus Q1. Penelitian ini memanfaatkan data akademik anonim untuk memprediksi risiko mahasiswa tidak melanjutkan studi.',
                    'Model yang dikembangkan mencapai akurasi di atas 88% dan kini diuji coba sebagai sistem peringatan dini bagi dosen pembimbing akademik.',
                    'Publikasi ini menambah daftar luaran riset prodi dan menjadi bagian dari roadmap penelitian kelompok keahlian Sains Data.',
                ],
            ],
            [
                'code' => 'b3',
                'kategori_badge' => 'Pengabdian',
                'label_pojok' => 'Dampak Wilayah',
                'label_tanggal' => '15 Februari 2025',
                'tanggal_publikasi' => '2025-02-15',
                'menit_baca' => 5,
                'judul' => 'Pelatihan tata kelola data UMKM berbasis cloud bersama desa binaan',
                'ringkasan' => 'Puluhan pelaku usaha lokal dibantu menstandarkan pencatatan inventaris dan penjualan digital.',
                'penulis' => 'Tim Pengabdian',
                'inisial_penulis' => 'TP',
                'jalur_gambar' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=1200&q=80',
                'isi_berita' => [
                    'Tim pengabdian masyarakat dosen dan mahasiswa Sistem Informasi menggelar pelatihan tata kelola data bagi pelaku UMKM di desa binaan. Peserta diajak memindahkan pencatatan manual ke aplikasi spreadsheet berbasis cloud.',
                    'Selain pelatihan, tim juga menyediakan pendampingan selama satu bulan agar peserta dapat menerapkan pencatatan secara konsisten.',
                    'Kegiatan ini merupakan bagian dari program pengabdian tahunan prodi yang berfokus pada literasi digital masyarakat.',
                ],
            ],
            [
                'code' => 'b4',
                'kategori_badge' => 'Akademik',
                'label_pojok' => 'Akreditasi',
                'label_tanggal' => '3 Februari 2025',
                'tanggal_publikasi' => '2025-02-03',
                'menit_baca' => 3,
                'judul' => 'Prodi Sistem Informasi raih akreditasi Unggul dari LAM INFOKOM',
                'ringkasan' => 'Capaian ini menegaskan mutu tata kelola, kurikulum, dan luaran tridharma prodi.',
                'penulis' => 'Gugus Penjaminan Mutu',
                'inisial_penulis' => 'GM',
                'jalur_gambar' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=1200&q=80',
                'isi_berita' => [
                    'Program Studi Sistem Informasi UNUSA resmi memperoleh status akreditasi Unggul dari LAM INFOKOM. Penilaian mencakup tata kelola, kurikulum, sumber daya, hingga luaran penelitian dan pengabdian.',
                    'Status ini berlaku selama lima tahun dan menjadi landasan prodi untuk memperluas kerja sama nasional maupun internasional.',
                ],
            ],
            [
                'code' => 'b5',
                'kategori_badge' => 'Kegiatan',
                'label_pojok' => 'Workshop',
                'label_tanggal' => '27 Januari 2025',
                'tanggal_publikasi' => '2025-01-27',
                'menit_baca' => 4,
                'judul' => 'Workshop analitik data dengan Python untuk mahasiswa baru',
                'ringkasan' => 'Lebih dari 150 mahasiswa mengikuti workshop pengenalan analisis data menggunakan Python.',
                'penulis' => 'Himpunan Mahasiswa',
                'inisial_penulis' => 'HM',
                'jalur_gambar' => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=1200&q=80',
                'isi_berita' => [
                    'Himpunan Mahasiswa Sistem Informasi menyelenggarakan workshop analitik data dengan Python bagi mahasiswa baru. Materi meliputi dasar pemrograman, pengolahan data dengan pandas, hingga visualisasi sederhana.',
                    'Kegiatan ini diharapkan membekali mahasiswa sejak awal perkuliahan dengan keterampilan yang dibutuhkan industri.',
                ],
            ],
            [
                'code' => 'b6',
                'kategori_badge' => 'Prestasi Mahasiswa',
                'label_pojok' => 'Internasional',
                'label_tanggal' => '12 Januari 2025',
                'tanggal_publikasi' => '2025-01-12',
                'menit_baca' => 4,
                'judul' => 'Mahasiswa SI lolos program pertukaran pelajar ke Malaysia',
                'ringkasan' => 'Empat mahasiswa akan mengikuti perkuliahan satu semester di universitas mitra.',
                'penulis' => 'Kantor Urusan Internasional',
                'inisial_penulis' => 'KI',
                'jalur_gambar' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=1200&q=80',
                'isi_berita' => [
                    'Empat mahasiswa Sistem Informasi dinyatakan lolos seleksi program pertukaran pelajar ke universitas mitra di Malaysia. Selama satu semester, mereka akan mengikuti perkuliahan dan proyek kolaboratif.',
                    'Mata kuliah yang diambil akan dikonversi ke dalam kurikulum prodi melalui skema rekognisi kredit.',
                ],
            ],
        ];

        foreach (array_values($items) as $idx => $item) {
            $ts = now()->subDays($idx + 1);
            $article = new NewsArticle([
                ...$item,
                'dikelola_oleh' => $manager->id,
            ]);
            $article->created_at = $ts;
            $article->updated_at = $ts;
            $article->save();
        }
    }

    protected function seedCourses(User $manager): void
    {
        $items = [
            ['code' => 'MK-SI101', 'tahun' => '2024', 'semester' => 1, 'kode_mk' => 'SI101', 'nama_mk' => 'Pengantar Sistem Informasi', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI102', 'tahun' => '2024', 'semester' => 1, 'kode_mk' => 'SI102', 'nama_mk' => 'Algoritma dan Pemrograman', 'sks' => 4, 'pilihan' => false],
            ['code' => 'MK-SI103', 'tahun' => '2024', 'semester' => 1, 'kode_mk' => 'SI103', 'nama_mk' => 'Matematika Diskrit', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI104', 'tahun' => '2024', 'semester' => 1, 'kode_mk' => 'SI104', 'nama_mk' => 'Pendidikan Aswaja', 'sks' => 2, 'pilihan' => false],
            ['code' => 'MK-SI201', 'tahun' => '2024', 'semester' => 2, 'kode_mk' => 'SI201', 'nama_mk' => 'Struktur Data', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI202', 'tahun' => '2024', 'semester' => 2, 'kode_mk' => 'SI202', 'nama_mk' => 'Basis Data', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI203', 'tahun' => '2024', 'semester' => 2, 'kode_mk' => 'SI203', 'nama_mk' => 'Statistika', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI204', 'tahun' => '2024', 'semester' => 2, 'kode_mk' => 'SI204', 'nama_mk' => 'Proses Bisnis', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI301', 'tahun' => '2024', 'semester' => 3, 'kode_mk' => 'SI301', 'nama_mk' => 'Pemrograman Web', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI302', 'tahun' => '2024', 'semester' => 3, 'kode_mk' => 'SI302', 'nama_mk' => 'Analisis dan Perancangan Sistem', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI303', 'tahun' => '2024', 'semester' => 3, 'kode_mk' => 'SI303', 'nama_mk' => 'Jaringan Komputer', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI304', 'tahun' => '2024', 'semester' => 3, 'kode_mk' => 'SI304', 'nama_mk' => 'Sistem Informasi Manajemen', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI401', 'tahun' => '2024', 'semester' => 4, 'kode_mk' => 'SI401', 'nama_mk' => 'Rekayasa Perangkat Lunak', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI402', 'tahun' => '2024', 'semester' => 4, 'kode_mk' => 'SI402', 'nama_mk' => 'Interaksi Manusia dan Komputer', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI403', 'tahun' => '2024', 'semester' => 4, 'kode_mk' => 'SI403', 'nama_mk' => 'Manajemen Proyek SI', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI404', 'tahun' => '2024', 'semester' => 4, 'kode_mk' => 'SI404', 'nama_mk' => 'Data Warehouse', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI501', 'tahun' => '2024', 'semester' => 5, 'kode_mk' => 'SI501', 'nama_mk' => 'Tata Kelola TI', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI502', 'tahun' => '2024', 'semester' => 5, 'kode_mk' => 'SI502', 'nama_mk' => 'Analitika Data', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI503', 'tahun' => '2024', 'semester' => 5, 'kode_mk' => 'SI503', 'nama_mk' => 'Enterprise Resource Planning', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI551', 'tahun' => '2024', 'semester' => 5, 'kode_mk' => 'SI551', 'nama_mk' => 'Machine Learning', 'sks' => 3, 'pilihan' => true],
            ['code' => 'MK-SI601', 'tahun' => '2024', 'semester' => 6, 'kode_mk' => 'SI601', 'nama_mk' => 'Audit Sistem Informasi', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI602', 'tahun' => '2024', 'semester' => 6, 'kode_mk' => 'SI602', 'nama_mk' => 'Keamanan Sistem Informasi', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI603', 'tahun' => '2024', 'semester' => 6, 'kode_mk' => 'SI603', 'nama_mk' => 'Metodologi Penelitian', 'sks' => 2, 'pilihan' => false],
            ['code' => 'MK-SI651', 'tahun' => '2024', 'semester' => 6, 'kode_mk' => 'SI651', 'nama_mk' => 'UI/UX Design', 'sks' => 3, 'pilihan' => true],
            ['code' => 'MK-SI701', 'tahun' => '2024', 'semester' => 7, 'kode_mk' => 'SI701', 'nama_mk' => 'Kerja Praktik', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI702', 'tahun' => '2024', 'semester' => 7, 'kode_mk' => 'SI702', 'nama_mk' => 'Kuliah Kerja Nyata', 'sks' => 3, 'pilihan' => false],
            ['code' => 'MK-SI751', 'tahun' => '2024', 'semester' => 7, 'kode_mk' => 'SI751', 'nama_mk' => 'E-Government', 'sks' => 3, 'pilihan' => true],
            ['code' => 'MK-SI752', 'tahun' => '2024', 'semester' => 7, 'kode_mk' => 'SI752', 'nama_mk' => 'Business Intelligence', 'sks' => 3, 'pilihan' => true],
            ['code' => 'MK-SI801', 'tahun' => '2024', 'semester' => 8, 'kode_mk' => 'SI801', 'nama_mk' => 'Skripsi', 'sks' => 6, 'pilihan' => false],
        ];

        foreach ($items as $item) {
            Course::create([
                ...$item,
                'dikelola_oleh' => $manager->id,
            ]);
        }
    }
}
