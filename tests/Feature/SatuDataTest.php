<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DataForm;
use App\Models\DataRequest;
use App\Models\Dataset;
use App\Models\FormSubmission;
use App\Models\NewsArticle;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubmissionValue;
use App\Models\User;
use App\Models\Visualization;
use App\Services\ChartPaletteService;
use App\Services\SpreadsheetExportService;
use Database\Seeders\SatuDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class SatuDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SatuDataSeeder::class);
    }

    public function test_clean_slate_seed_and_public_pages_render_without_dummy_input_data(): void
    {
        // Clean-slate verification: 0 forms, submissions, datasets, visualizations, and requests
        $this->assertSame(0, DataForm::query()->count());
        $this->assertSame(0, FormSubmission::query()->count());
        $this->assertSame(0, SubmissionValue::query()->count());
        $this->assertSame(0, Dataset::query()->count());
        $this->assertSame(0, DataRequest::query()->count());
        $this->assertSame(0, Visualization::query()->count());

        // Master data preserved
        $this->assertSame(3, Role::query()->count());
        $this->assertSame(14, Permission::query()->count());
        $this->assertSame(5, User::query()->count());
        $this->assertSame(13, Category::query()->count());

        $news = NewsArticle::query()->firstOrFail();
        $urls = [
            '/',
            '/dashboard',
            '/dataset',
            '/berita',
            '/berita/'.$news->code,
            '/pengisian-data',
            '/tentang-kami',
            '/login',
        ];

        foreach ($urls as $url) {
            $response = $this->get($url);
            $response->assertStatus(200);
            $response->assertDontSee('cdn.tailwindcss.com', false);
        }

        $this->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('Belum ada visualisasi dashboard yang diterbitkan', false)
            ->assertDontSee('Mahasiswa Aktif', false);

        $this->get('/dataset')
            ->assertStatus(200)
            ->assertSee('Belum ada dataset yang diterbitkan', false);

        $this->get('/pengisian-data')
            ->assertStatus(200)
            ->assertSee('Belum ada formulir pengumpulan data aktif', false);
    }

    public function test_language_switcher_persists_locale_in_session(): void
    {
        $this->from('/')->post(route('lang.switch', 'en'))
            ->assertRedirect('/')
            ->assertSessionHas('lang', 'en');

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('About us', false);

        $this->from('/')->post(route('lang.switch', 'id'))
            ->assertRedirect('/')
            ->assertSessionHas('lang', 'id');

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Tentang kami', false);
    }

    public function test_authentication_and_30_minute_idle_timeout(): void
    {
        $this->from('/login')->post(route('login.post'), [
            'nim' => 'admin',
            'password' => 'wrongpass',
        ])->assertRedirect('/login')
            ->assertSessionHas('error');
        $this->assertGuest();

        $inactive = User::factory()->create([
            'code' => 'U-TEST-INACTIVE',
            'nama_pengguna' => 'Nonaktif User',
            'nim' => '9999999999',
            'id_peran' => User::resolveRoleId('produsen'),
            'jenis' => 'Mahasiswa',
            'kata_sandi' => Hash::make('secret123'),
            'aktif' => false,
        ]);

        $this->from('/login')->post(route('login.post'), [
            'nim' => $inactive->nim,
            'password' => 'secret123',
        ])->assertRedirect('/login')
            ->assertSessionHas('error', 'Akun Anda dinonaktifkan. Hubungi Admin prodi.');
        $this->assertGuest();

        foreach ([
            ['admin', 'admin123'],
            ['walidata', 'wali123'],
            ['1462200031', 'mhs123'],
        ] as [$nim, $pwd]) {
            $this->post(route('login.post'), [
                'nim' => $nim,
                'password' => $pwd,
            ])->assertRedirect(route('panel.ringkasan'));
            $this->assertAuthenticated();
            $this->post(route('logout'))->assertRedirect(route('login'));
            $this->assertGuest();
        }

        $admin = User::query()->where('nim', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['last_activity_at' => now()->subMinutes(31)->timestamp])
            ->get(route('panel.ringkasan'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('notice');

        $this->assertGuest();

        $inactive->update(['aktif' => false]);
        $this->actingAs($inactive)
            ->withSession(['last_activity_at' => now()->timestamp])
            ->get(route('panel.ringkasan'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_rbac_enforcement_and_all_panel_views_render(): void
    {
        $this->get(route('panel.ringkasan'))->assertRedirect(route('login'));

        $produsen = User::query()->where('nim', '1462200031')->firstOrFail();
        $wali = User::query()->where('nim', 'walidata')->firstOrFail();
        $admin = User::query()->where('nim', 'admin')->firstOrFail();
        $cat = Category::query()->firstOrFail();

        $sampleForm = DataForm::create([
            'code' => 'F-RBAC-01',
            'id_kategori' => $cat->id,
            'judul' => 'Formulir RBAC',
            'deskripsi' => 'Uji akses',
            'tahun' => 2026,
            'periode' => 'Ganjil',
            'target' => 'Semua',
            'tanggal_waktu' => now()->addDays(5),
            'status_aktif' => true,
            'dibuat_oleh' => $admin->id,
        ]);
        $sampleForm->syncFields([['id' => 'q1', 'label' => 'Pertanyaan', 'type' => 'text', 'required' => true]]);

        $sampleDataset = Dataset::create([
            'code' => 'DS-RBAC-01',
            'id_kategori' => $cat->id,
            'id_formulir_sumber' => $sampleForm->id,
            'judul' => 'Dataset RBAC',
            'deskripsi' => 'Uji akses dataset',
            'tahun' => '2026',
            'periode' => 'Ganjil',
            'tipe_akses' => 'public',
            'status_publikasi' => 'terpublikasi',
            'format' => 'CSV',
            'kolom' => ['Kolom', 'Jumlah'],
            'baris' => [['A', 1]],
            'dibuat_oleh' => $admin->id,
        ]);

        $this->assertTrue($admin->hasPermission('preprocessing_data'));
        $this->assertTrue($wali->hasPermission('preprocessing_data'));
        $this->assertFalse($wali->hasPermission('kelola_pengguna'));
        $this->assertTrue($produsen->hasPermission('isi_formulir'));

        // Produsen access
        $this->actingAs($produsen)->get(route('panel.ringkasan'))->assertStatus(200);
        $this->actingAs($produsen)->get(route('panel.isi'))->assertStatus(200);
        $this->actingAs($produsen)->get(route('panel.riwayat'))->assertStatus(200);

        foreach ([
            route('panel.formulir'),
            route('panel.preprocessing'),
            route('panel.data'),
            route('panel.kategori'),
            route('panel.dataset'),
            route('panel.visualisasi'),
            route('panel.permohonan'),
            route('panel.pengguna'),
            route('panel.konten'),
        ] as $forbiddenUrl) {
            $this->actingAs($produsen)->get($forbiddenUrl)->assertStatus(403);
        }

        // Wali Data access
        $this->actingAs($wali)->get(route('panel.ringkasan'))->assertStatus(200);
        $this->actingAs($wali)->get(route('panel.riwayat'))->assertStatus(200);
        $this->actingAs($wali)->get(route('panel.formulir'))->assertStatus(200);
        $this->actingAs($wali)->get(route('panel.preprocessing'))->assertStatus(200);
        $this->actingAs($wali)->get(route('panel.data'))->assertRedirect(route('panel.preprocessing'));
        $this->actingAs($wali)->get(route('panel.kategori'))->assertStatus(200);
        $this->actingAs($wali)->get(route('panel.dataset'))->assertStatus(200);
        $this->actingAs($wali)->get(route('panel.visualisasi'))->assertStatus(200);

        foreach (['menunggu', 'disetujui', 'ditolak', 'all'] as $tab) {
            $this->actingAs($wali)->get(route('panel.permohonan', ['tab' => $tab]))->assertStatus(200);
        }

        $this->actingAs($wali)->get(route('panel.isi'))->assertStatus(403);
        $this->actingAs($wali)->get(route('panel.pengguna'))->assertStatus(403);
        $this->actingAs($wali)->get(route('panel.konten'))->assertStatus(403);

        $this->actingAs($wali)->delete(route('panel.formulir.destroy', $sampleForm->code))->assertStatus(403);
        $this->actingAs($wali)->delete(route('panel.kategori.destroy', $cat->id))->assertStatus(403);
        $this->actingAs($wali)->delete(route('panel.dataset.destroy', $sampleDataset->code))->assertStatus(403);

        // Admin access (includes form builder, isi formulir, pre-processing, dataset, admin panels)
        $this->actingAs($admin)->get(route('panel.ringkasan'))->assertStatus(200);
        $this->actingAs($admin)->get(route('panel.riwayat'))->assertStatus(200);
        $this->actingAs($admin)->get(route('panel.formulir'))->assertStatus(200);
        $this->actingAs($admin)->get(route('panel.isi'))->assertStatus(200);
        $this->actingAs($admin)->get(route('panel.preprocessing'))->assertStatus(200);
        $this->actingAs($admin)->get(route('panel.kategori'))->assertStatus(200);
        $this->actingAs($admin)->get(route('panel.dataset'))->assertStatus(200);
        $this->actingAs($admin)->get(route('panel.visualisasi'))->assertStatus(200);
        $this->actingAs($admin)->get(route('panel.permohonan'))->assertStatus(200);
        $this->actingAs($admin)->get(route('panel.pengguna'))->assertStatus(200);

        foreach (['profil', 'dosen', 'prestasi', 'berita', 'kurikulum'] as $tab) {
            $this->actingAs($admin)->get(route('panel.konten', ['tab' => $tab]))->assertStatus(200);
        }
    }

    public function test_gform_creation_submission_radio_checkbox_single_response_limit_and_attachment(): void
    {
        Storage::fake('local');

        $wali = User::query()->where('nim', 'walidata')->firstOrFail();
        $mhs1 = User::query()->where('nim', '1462200031')->firstOrFail();
        $mhs2 = User::query()->where('nim', '1462300012')->firstOrFail();
        $category = Category::query()->firstOrFail();

        // Step 1: Wali Data creates a Google-Forms-style instrument with radio, checkbox, file & single-response limit
        $fieldsJson = json_encode([
            ['id' => 'judul', 'label' => 'Judul Kegiatan', 'help' => 'Isi sesuai sertifikat resmi', 'type' => 'text', 'required' => true],
            ['id' => 'tingkat', 'label' => 'Tingkat', 'help' => '', 'type' => 'radio', 'required' => true, 'options' => ['Regional', 'Nasional']],
            ['id' => 'peran', 'label' => 'Peran Tim', 'help' => 'Boleh lebih dari satu', 'type' => 'checkbox', 'required' => true, 'options' => ['Ketua', 'Analis Data', 'UI/UX']],
            ['id' => 'poin', 'label' => 'Poin Capaian', 'help' => '', 'type' => 'number', 'required' => true],
            ['id' => 'bukti', 'label' => 'Bukti PDF', 'help' => 'Unggah berkas PDF', 'type' => 'file', 'required' => true],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($wali)->post(route('panel.formulir.store'), [
            'title' => 'Pendataan Rekognisi Mahasiswa 2026',
            'description' => 'Formulir GForm mahasiswa',
            'category_id' => $category->id,
            'tahun' => '2026',
            'periode' => 'Ganjil',
            'target' => 'Mahasiswa',
            'batasi_satu_respon' => '1',
            'deadline' => now()->addDays(7)->format('Y-m-d\TH:i'),
            'fields_json' => $fieldsJson,
        ])->assertRedirect(route('panel.formulir'))
            ->assertSessionHas('success', 'Formulir Berhasil Dibuat');

        $form = DataForm::query()->where('judul', 'Pendataan Rekognisi Mahasiswa 2026')->firstOrFail();
        $this->assertTrue($form->batasi_satu_respon);
        $this->assertSame('belum', $form->status_preprocessing);
        $this->assertSame(5, $form->kolomFormulir()->count());

        // Step 2: Public dedicated GForm route /formulir/{code}
        $this->actingAs($mhs1)
            ->get(route('formulir.show', $form->code))
            ->assertStatus(200)
            ->assertSee('Isi sesuai sertifikat resmi', false)
            ->assertSee('type="radio"', false)
            ->assertSee('type="checkbox"', false);

        $pdf = UploadedFile::fake()->create('sertifikat.pdf', 100, 'application/pdf');

        $res = $this->actingAs($mhs1)->post(route('panel.isi.submit', $form->code), [
            'values' => [
                'judul' => 'Gemastik   Data Mining',
                'tingkat' => 'Nasional',
                'peran' => ['Ketua', 'Analis Data'],
                'poin' => '100',
            ],
            'files' => ['bukti' => $pdf],
        ]);
        $res->assertRedirect();
        $this->assertStringContainsString('Formulir Berhasil Disimpan', (string) session('success'));

        $sub = FormSubmission::query()->where('id_formulir', $form->id)->where('diisi_oleh', $mhs1->id)->firstOrFail();
        $this->assertSame('mentah', $sub->status_validasi);
        $this->assertSame('formulir', $sub->sumber_input);
        $this->assertSame('Ketua, Analis Data', $sub->values['peran']);
        $this->assertSame('proses', $form->fresh()->status_preprocessing);

        // Raw value preserved in nilai_mentah
        $this->assertSame('Gemastik   Data Mining', $sub->rawValues['judul'] ?? '');

        // Attachments readable only by owner & staff
        $this->actingAs($mhs1)->get(route('panel.submissions.file', [$sub->code, 'bukti']))->assertStatus(200);
        $this->actingAs($wali)->get(route('panel.submissions.file', [$sub->code, 'bukti']))->assertStatus(200);
        $this->actingAs($mhs2)->get(route('panel.submissions.file', [$sub->code, 'bukti']))->assertStatus(403);

        // Single-response guard blocks second submission from same account
        $this->actingAs($mhs1)->postJson(route('panel.isi.submit', $form->code), [
            'values' => ['judul' => 'Kedua', 'tingkat' => 'Regional', 'peran' => ['Ketua'], 'poin' => '50'],
        ])->assertStatus(422);

        // Wali Data toggles form closed and open
        $this->actingAs($wali)->patch(route('panel.formulir.toggle', $form->code))->assertRedirect();
        $this->assertFalse($form->fresh()->isOpen());
        $this->actingAs($mhs2)->postJson(route('panel.isi.submit', $form->code), [
            'values' => ['judul' => 'Telat'],
        ])->assertStatus(422);

        $this->actingAs($wali)->patch(route('panel.formulir.toggle', $form->code))->assertRedirect();
        $this->assertTrue($form->fresh()->isOpen());
    }

    public function test_full_preprocessing_pipeline_gate_and_dataset_builder_modes(): void
    {
        $wali = User::query()->where('nim', 'walidata')->firstOrFail();
        $category = Category::query()->firstOrFail();

        $form = DataForm::create([
            'code' => 'F-PREPROC-01',
            'id_kategori' => $category->id,
            'judul' => 'Pendataan Dana Hibah & Bidang Minat 2026',
            'deskripsi' => 'Instrumen GForm untuk uji pre-processing',
            'tahun' => 2026,
            'periode' => 'Ganjil',
            'target' => 'Semua',
            'tanggal_waktu' => now()->addDays(10),
            'status_aktif' => true,
            'dibuat_oleh' => $wali->id,
        ]);
        $form->syncFields([
            ['id' => 'prodi', 'label' => 'Kelompok Riset', 'type' => 'radio', 'required' => true, 'options' => ['Data Science', 'Sistem Enterprise']],
            ['id' => 'topik', 'label' => 'Topik', 'type' => 'checkbox', 'required' => true, 'options' => ['BI', 'ERP']],
            ['id' => 'dana', 'label' => 'Nominal Dana', 'type' => 'number', 'required' => true],
        ]);

        // Import messy CSV export from GForm:
        // Row 1: older clean duplicate
        // Row 2: newer messy case, internal spaces & Indonesian currency (kept by deduplication)
        // Row 3: missing nominal dana
        // Row 4: valid Sistem Enterprise row
        $csv = "Timestamp,Kelompok Riset,Topik,Nominal Dana\r\n"
            ."2026-10-01 09:00,Data Science,\"BI, ERP\",10000000\r\n"
            ."2026-10-01 09:05,data   science,\"bi,  erp\",Rp 10.000.000\r\n"
            ."2026-10-01 09:10,Sistem Enterprise,ERP,\r\n"
            ."2026-10-01 09:15,Sistem Enterprise,BI,20000000\r\n";

        $csvFile = UploadedFile::fake()->createWithContent('gform-export.csv', $csv);

        // GATE CHECK 0: Open form MUST BE CLOSED before entering Pre-processing Data
        $this->actingAs($wali)
            ->get(route('panel.preprocessing.show', $form->code))
            ->assertRedirect(route('panel.preprocessing'))
            ->assertSessionHas('error');
        $this->actingAs($wali)
            ->from(route('panel.preprocessing'))
            ->post(route('panel.preprocessing.import', $form->code), ['file' => $csvFile])
            ->assertRedirect(route('panel.preprocessing'))
            ->assertSessionHas('error');

        $this->actingAs($wali)
            ->patch(route('panel.formulir.toggle', $form->code))
            ->assertRedirect();
        $this->assertTrue($form->fresh()->isClosed());

        $this->actingAs($wali)
            ->post(route('panel.preprocessing.import', $form->code), ['file' => $csvFile])
            ->assertRedirect(route('panel.preprocessing', ['form' => $form->code]))
            ->assertSessionHas('success');

        $this->assertSame(4, $form->submissions()->count());
        $this->assertSame('proses', $form->fresh()->status_preprocessing);

        // GATE CHECK 1: Dataset creation MUST BE BLOCKED before pre-processing finalization
        $this->actingAs($wali)->from(route('panel.dataset'))
            ->post(route('panel.dataset.from-form'), [
                'form_id' => $form->id,
                'mode' => 'rinci',
                'picked_cols' => ['prodi', 'dana'],
                'title' => 'Dataset Sebelum Pre-processing',
                'category_id' => $category->id,
                'tahun' => '2026',
                'periode' => 'Ganjil',
                'access' => 'public',
                'format' => 'CSV',
            ])
            ->assertRedirect(route('panel.dataset'))
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('dataset', ['judul' => 'Dataset Sebelum Pre-processing']);

        // GATE CHECK 2: Finalization MUST FAIL while issues remain (duplicate + missing)
        $this->actingAs($wali)->post(route('panel.preprocessing.finalize', $form->code))
            ->assertRedirect(route('panel.preprocessing', ['form' => $form->code]))
            ->assertSessionHas('error');
        $this->assertSame('proses', $form->fresh()->status_preprocessing);

        // Step A: Normalize batch (cleans spaces, radio/checkbox casing, and Rp 10.000.000 -> 10000000)
        $this->actingAs($wali)->post(route('panel.preprocessing.normalize', $form->code), [
            'normalize_numbers' => '1',
            'text_case' => 'none',
        ])->assertRedirect()->assertSessionHas('success');

        // Step B: Deduplicate (marks 1 duplicate as ditolak)
        $this->actingAs($wali)->post(route('panel.preprocessing.deduplicate', $form->code), [
            'scope' => 'same_values',
            'action' => 'reject',
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertSame(1, $form->submissions()->where('status_validasi', 'ditolak')->count());

        // Step C: Impute missing value using mean of remaining active numeric values ((10000000 + 20000000)/2 = 15000000)
        $this->actingAs($wali)->post(route('panel.preprocessing.impute', $form->code), [
            'field_code' => 'dana',
            'strategy' => 'mean',
        ])->assertRedirect()->assertSessionHas('success');

        // Step D: Finalize pre-processing -> marks 3 active rows valid and status_preprocessing = 'siap'
        $this->actingAs($wali)->post(route('panel.preprocessing.finalize', $form->code))
            ->assertRedirect(route('panel.preprocessing', ['form' => $form->code]))
            ->assertSessionHas('success');

        $form->refresh();
        $this->assertTrue($form->isPreprocessed());
        $this->assertSame(3, $form->submissions()->where('status_validasi', 'valid')->count());
        $this->assertNotEmpty($form->ringkasan_preprocessing['logs'] ?? []);

        // Ensure nilai_mentah was preserved for the kept messy row while nilai was normalized
        $keptNormalizedSub = FormSubmission::query()
            ->where('id_formulir', $form->id)
            ->where('status_validasi', 'valid')
            ->get()
            ->first(fn (FormSubmission $s): bool => ($s->rawValues['prodi'] ?? '') === 'data   science');
        $this->assertNotNull($keptNormalizedSub);
        $this->assertSame('Data Science', $keptNormalizedSub->values['prodi']);
        $this->assertSame('10000000', $keptNormalizedSub->values['dana']);
        $this->assertSame('Rp 10.000.000', $keptNormalizedSub->rawValues['dana']);

        // Build Mode 1: Dataset Rinci as DRAFT with column aliasing & sensitive masking
        $this->actingAs($wali)->post(route('panel.dataset.from-form'), [
            'form_id' => $form->id,
            'mode' => 'rinci',
            'picked_cols' => ['_nama', 'prodi', 'topik', 'dana'],
            'col_aliases' => ['_nama' => 'Responden', 'dana' => 'Dana Disetujui'],
            'masked_cols' => ['_nama'],
            'title' => 'Data Rinci Hibah Riset 2026',
            'category_id' => $category->id,
            'tahun' => '2026',
            'periode' => 'Ganjil',
            'access' => 'public',
            'publish_status' => 'draft',
            'format' => 'XLSX',
        ])->assertRedirect(route('panel.dataset'))
            ->assertSessionHas('success');

        $rinci = Dataset::query()->where('judul', 'Data Rinci Hibah Riset 2026')->firstOrFail();
        $this->assertSame('draft', $rinci->status_publikasi);
        $this->assertSame(['Responden', 'Kelompok Riset', 'Topik', 'Dana Disetujui'], $rinci->kolom);
        $this->assertCount(3, $rinci->baris);
        $this->assertStringStartsWith('Responden #', (string) $rinci->baris[0][0]);

        // Draft datasets are hidden from public catalog & preview
        $this->post(route('logout'));
        $this->get(route('dataset'))->assertDontSee('Data Rinci Hibah Riset 2026', false);
        $this->get(route('dataset.preview', $rinci->code))->assertStatus(403);

        // Publish draft dataset -> now visible on public catalog
        $this->actingAs($wali)->patchJson(route('panel.dataset.publish', $rinci->code), [
            'status_publikasi' => 'terpublikasi',
        ])->assertStatus(200)->assertJsonPath('status_publikasi', 'terpublikasi');

        $this->post(route('logout'));
        $this->get(route('dataset'))->assertStatus(200)->assertSee('Data Rinci Hibah Riset 2026', false);

        // Build Mode 2: Dataset Rekap with SUM aggregation per Kelompok Riset (Data Science: 10M, Sistem Enterprise: 35M)
        $this->actingAs($wali)->post(route('panel.dataset.from-form'), [
            'form_id' => $form->id,
            'mode' => 'rekap',
            'group_field_id' => 'prodi',
            'agg_func' => 'sum',
            'agg_field_id' => 'dana',
            'title' => 'Total Dana Hibah per Kelompok Riset 2026',
            'category_id' => $category->id,
            'tahun' => '2026',
            'periode' => 'Ganjil',
            'access' => 'public',
            'publish_status' => 'terpublikasi',
            'format' => 'CSV',
        ])->assertRedirect(route('panel.dataset'))
            ->assertSessionHas('success');

        $rekap = Dataset::query()->where('judul', 'Total Dana Hibah per Kelompok Riset 2026')->firstOrFail();
        $this->assertSame(['Kelompok Riset', 'Total Nominal Dana'], $rekap->kolom);
        $rowsAssoc = collect($rekap->baris)->mapWithKeys(fn (array $r): array => [(string) $r[0] => $r[1]])->all();
        $this->assertSame(10000000, $rowsAssoc['Data Science']);
        $this->assertSame(35000000, $rowsAssoc['Sistem Enterprise']);

        // Also verify Re-sync when new XLSX submission arrives & completes pre-processing
        $exporter = app(SpreadsheetExportService::class);
        $xlsxRes = $exporter->downloadXlsx('extra-row', ['Kelompok Riset', 'Topik', 'Nominal Dana'], [['Data Science', 'BI', '5000000']]);
        $xlsxFile = UploadedFile::fake()->createWithContent('extra.xlsx', (string) $xlsxRes->getContent());

        $this->actingAs($wali)->post(route('panel.preprocessing.import', $form->code), ['file' => $xlsxFile])->assertRedirect();
        $this->assertSame('proses', $form->fresh()->status_preprocessing);

        $this->actingAs($wali)->post(route('panel.preprocessing.finalize', $form->code))->assertRedirect()->assertSessionHas('success');
        $this->actingAs($wali)->post(route('panel.dataset.sync', $rekap->code))->assertRedirect(route('panel.dataset'))->assertSessionHas('success');

        $rekap->refresh();
        $syncedAssoc = collect($rekap->baris)->mapWithKeys(fn (array $r): array => [(string) $r[0] => $r[1]])->all();
        $this->assertSame(15000000, $syncedAssoc['Data Science']);
        $this->assertSame(35000000, $syncedAssoc['Sistem Enterprise']);
    }

    public function test_private_dataset_request_approval_rejection_and_csv_xlsx_downloads(): void
    {
        $wali = User::query()->where('nim', 'walidata')->firstOrFail();
        $cat = Category::query()->firstOrFail();

        $publicDs = Dataset::create([
            'code' => 'DS-PUB-01',
            'id_kategori' => $cat->id,
            'judul' => 'Dataset Publik Uji',
            'deskripsi' => 'Publik',
            'tahun' => '2026',
            'periode' => 'Ganjil',
            'tipe_akses' => 'public',
            'status_publikasi' => 'terpublikasi',
            'format' => 'XLSX',
            'kolom' => ['Kategori', 'Total'],
            'baris' => [['Akademik', 42], ['Riset', 18]],
            'dibuat_oleh' => $wali->id,
        ]);

        $privateDs = Dataset::create([
            'code' => 'DS-PRIV-01',
            'id_kategori' => $cat->id,
            'judul' => 'Dataset Private Uji',
            'deskripsi' => 'Khusus disetujui',
            'tahun' => '2026',
            'periode' => 'Ganjil',
            'tipe_akses' => 'private',
            'status_publikasi' => 'terpublikasi',
            'format' => 'XLSX',
            'kolom' => ['Nama', 'Nilai'],
            'baris' => [['Ahmad', 95]],
            'dibuat_oleh' => $wali->id,
        ]);

        $this->get(route('dataset.preview', $publicDs->code))
            ->assertStatus(200)
            ->assertJsonPath('id', $publicDs->code);

        $publicCsv = $this->get(route('dataset.download', [$publicDs->code, 'csv']));
        $publicCsv->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $publicCsv->headers->get('content-type'));

        $publicXlsx = $this->get(route('dataset.download', [$publicDs->code, 'xlsx']));
        $publicXlsx->assertStatus(200);
        $xlsxBytes = $publicXlsx->getContent();
        $this->assertNotEmpty($xlsxBytes);
        $xlsxPath = tempnam(sys_get_temp_dir(), 'test_xlsx_');
        $this->assertNotFalse($xlsxPath);
        file_put_contents($xlsxPath, $xlsxBytes);

        $zip = new ZipArchive;
        $opened = $zip->open($xlsxPath);
        $this->assertTrue($opened === true, 'XLSX harus valid ZipArchive.');
        $this->assertNotFalse($zip->locateName('xl/workbook.xml'));
        $this->assertNotFalse($zip->locateName('xl/worksheets/sheet1.xml'));
        $zip->close();
        @unlink($xlsxPath);

        $this->get(route('dataset.preview', $privateDs->code))->assertStatus(403);
        $this->get(route('dataset.download', [$privateDs->code, 'csv']))->assertStatus(403);
        $this->get(route('dataset.download', [$privateDs->code, 'xlsx']))->assertStatus(403);

        $req1Res = $this->postJson(route('dataset.request', $privateDs->code), [
            'nama' => 'Pemohon Pertama',
            'email' => 'pemohon1@student.unusa.ac.id',
            'hp' => '081234567890',
            'nim' => '1462200051',
            'tujuan' => 'Tugas Akhir / Skripsi',
            'deskripsi' => 'Analisis statistik alumni SI UNUSA.',
        ]);
        $req1Res->assertStatus(201);
        $req1Code = (string) $req1Res->json('request.id');

        $this->actingAs($wali)->from(route('panel.permohonan'))
            ->post(route('panel.permohonan.decide', $req1Code), [
                'status' => 'ditolak',
                'note' => '',
            ])
            ->assertRedirect(route('panel.permohonan'))
            ->assertSessionHasErrors('note');

        $this->actingAs($wali)->post(route('panel.permohonan.decide', $req1Code), [
            'status' => 'ditolak',
            'note' => 'Dokumen surat izin belum dilampirkan.',
        ])->assertRedirect(route('panel.permohonan', ['tab' => 'menunggu']));

        $this->post(route('logout'));

        $this->withSession(['my_request_codes' => [$req1Code]])
            ->get(route('dataset.preview', $privateDs->code))
            ->assertStatus(403);

        $req2Res = $this->postJson(route('dataset.request', $privateDs->code), [
            'nama' => 'Pemohon Kedua',
            'email' => 'pemohon2@student.unusa.ac.id',
            'hp' => '081298765432',
            'nim' => '1462200052',
            'tujuan' => 'Penelitian Dosen',
            'deskripsi' => 'Riset internal tracer study.',
        ]);
        $req2Res->assertStatus(201);
        $req2Code = (string) $req2Res->json('request.id');

        $this->actingAs($wali)->post(route('panel.permohonan.decide', $req2Code), [
            'status' => 'disetujui',
            'note' => 'Disetujui untuk riset internal.',
        ])->assertRedirect(route('panel.permohonan', ['tab' => 'menunggu']))
            ->assertSessionHas('success');

        $this->assertSame('disetujui', DataRequest::query()->where('code', $req2Code)->value('status'));

        $this->post(route('logout'));
        $guestWithApprovedSession = $this->withSession(['my_request_codes' => [$req2Code]]);

        $guestWithApprovedSession->get(route('dataset.preview', $privateDs->code))
            ->assertStatus(200)
            ->assertJsonPath('id', $privateDs->code);

        $guestWithApprovedSession->get(route('dataset.download', [$privateDs->code, 'csv']))
            ->assertStatus(200);

        $guestWithApprovedSession->get(route('dataset.download', [$privateDs->code, 'xlsx']))
            ->assertStatus(200);
    }

    public function test_visualizations_and_dashboard_visibility_toggle(): void
    {
        $wali1 = User::query()->where('nim', 'walidata')->firstOrFail();
        $wali2 = User::factory()->create([
            'code' => 'U-WALI-2',
            'nama_pengguna' => 'Wali Data Kedua',
            'nim' => 'walidata2',
            'id_peran' => User::resolveRoleId('wali'),
            'jenis' => null,
            'kata_sandi' => Hash::make('wali123'),
            'aktif' => true,
        ]);
        $cat = Category::query()->firstOrFail();

        $publicDs = Dataset::create([
            'code' => 'DS-VIZ-01',
            'id_kategori' => $cat->id,
            'judul' => 'Dataset Sumber Grafik 2026',
            'deskripsi' => 'Untuk uji visualisasi',
            'tahun' => '2026',
            'periode' => 'Ganjil',
            'tipe_akses' => 'public',
            'status_publikasi' => 'terpublikasi',
            'format' => 'CSV',
            'kolom' => ['Skema', 'Jumlah'],
            'baris' => [['Internal', 10], ['Dikti', 6]],
            'dibuat_oleh' => $wali1->id,
        ]);

        $title = 'Visualisasi Uji Dinamis 2026';

        $this->actingAs($wali1)->post(route('panel.visualisasi.store'), [
            'title' => $title,
            'dataset_id' => $publicDs->id,
            'label_col' => 0,
            'value_col' => 1,
            'kind' => 'bar',
            'color' => 'sky',
            'show' => '1',
        ])->assertRedirect(route('panel.visualisasi'))
            ->assertSessionHas('success');

        $viz = Visualization::query()->where('judul', $title)->firstOrFail();
        $this->assertTrue($viz->show);

        $this->get('/dashboard')->assertStatus(200)->assertSee($title, false);

        $this->actingAs($wali1)->patchJson(route('panel.visualisasi.toggle', $viz->code), ['show' => false])
            ->assertStatus(200)
            ->assertJsonPath('show', false);

        $this->get('/dashboard')->assertStatus(200)->assertDontSee($title, false);

        $this->actingAs($wali2)->delete(route('panel.visualisasi.destroy', $viz->code))
            ->assertStatus(403);

        $this->actingAs($wali1)->delete(route('panel.visualisasi.destroy', $viz->code))
            ->assertRedirect(route('panel.visualisasi'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('visualisasi_dashboard', ['id' => $viz->id]);
    }

    public function test_admin_crud_and_self_or_dependency_guards(): void
    {
        $admin = User::query()->where('nim', 'admin')->firstOrFail();

        $this->actingAs($admin)->from(route('panel.pengguna'))
            ->delete(route('panel.pengguna.destroy', $admin->id))
            ->assertRedirect(route('panel.pengguna'))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('pengguna', ['id' => $admin->id]);

        $this->actingAs($admin)->from(route('panel.pengguna'))
            ->put(route('panel.pengguna.update', $admin->id), [
                'name' => $admin->name,
                'nim' => $admin->nim,
                'role' => 'produsen',
                'jenis' => 'Mahasiswa',
                'aktif' => '1',
            ])
            ->assertRedirect(route('panel.pengguna'))
            ->assertSessionHas('error');
        $this->assertSame('admin', $admin->fresh()->role);

        $this->actingAs($admin)->post(route('panel.pengguna.store'), [
            'name' => 'Pengguna Baru Uji',
            'nim' => '1462200888',
            'role' => 'produsen',
            'jenis' => 'Dosen',
            'password' => 'secret123',
            'aktif' => '1',
        ])->assertRedirect(route('panel.pengguna'))
            ->assertSessionHas('success');

        $createdUser = User::query()->where('nim', '1462200888')->firstOrFail();

        $this->actingAs($admin)->put(route('panel.pengguna.update', $createdUser->id), [
            'name' => 'Pengguna Uji Diperbarui',
            'nim' => '1462200888',
            'role' => 'wali',
            'aktif' => '1',
        ])->assertRedirect(route('panel.pengguna'));

        $this->assertSame('wali', $createdUser->fresh()->role);

        $this->actingAs($admin)->delete(route('panel.pengguna.destroy', $createdUser->id))
            ->assertRedirect(route('panel.pengguna'));
        $this->assertDatabaseMissing('pengguna', ['id' => $createdUser->id]);

        $usedCat = Category::query()->firstOrFail();
        Dataset::create([
            'code' => 'DS-CAT-GUARD',
            'id_kategori' => $usedCat->id,
            'judul' => 'Penjaga Kategori',
            'deskripsi' => 'Uji relasi kategori',
            'tahun' => '2026',
            'periode' => 'Ganjil',
            'tipe_akses' => 'public',
            'status_publikasi' => 'terpublikasi',
            'format' => 'CSV',
            'kolom' => ['A'],
            'baris' => [['1']],
            'dibuat_oleh' => $admin->id,
        ]);

        $this->actingAs($admin)->from(route('panel.kategori'))
            ->delete(route('panel.kategori.destroy', $usedCat->id))
            ->assertRedirect(route('panel.kategori'))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('kategori', ['id' => $usedCat->id]);

        $this->actingAs($admin)->post(route('panel.kategori.store'), [
            'code' => 'KT-TEST-99',
            'name' => 'Kategori Sementara',
            'description' => 'Bisa dihapus',
        ])->assertRedirect(route('panel.kategori'));

        $unusedCat = Category::query()->where('code', 'KT-TEST-99')->firstOrFail();
        $this->actingAs($admin)->delete(route('panel.kategori.destroy', $unusedCat->id))
            ->assertRedirect(route('panel.kategori'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('kategori', ['id' => $unusedCat->id]);

        $this->actingAs($admin)->put(route('panel.konten.profil.update'), [
            'program' => 'S1 Sistem Informasi UNUSA',
            'gelar' => 'Sarjana Komputer (S.Kom.)',
            'akreditasi' => 'Unggul BAN-PT 2026',
            'berdiri' => '2014',
            'durasi' => '8 Semester',
            'fakultas' => 'Fakultas Ekonomi Bisnis dan Teknologi Digital',
            'email' => 'si@unusa.ac.id',
            'telp' => '031-8479070',
            'alamat' => 'Jl. Raya Jemursari No. 57 Surabaya',
            'deskripsi' => 'Deskripsi program studi yang telah diperbarui.',
            'visi' => 'Menjadi program studi sistem informasi unggul.',
            'misi_text' => "Misi pertama\nMisi kedua",
            'sejarah' => 'Sejarah prodi SI UNUSA.',
        ])->assertRedirect(route('panel.konten', ['tab' => 'profil']))
            ->assertSessionHas('success');

        $this->actingAs($admin)->post(route('panel.konten.dosen.store'), [
            'nama' => 'Dr. Uji Pengampu, S.Kom., M.T.',
            'nidn' => '0712345678',
            'pendidikan' => 'S3',
            'jabatan' => 'Lektor',
            'bidang' => 'Rekayasa Data',
            'email' => 'dosen.uji@unusa.ac.id',
        ])->assertRedirect(route('panel.konten', ['tab' => 'dosen']));

        $this->actingAs($admin)->post(route('panel.konten.prestasi.store'), [
            'judul' => 'Juara 1 Hackathon Satu Data 2026',
            'nama' => 'Tim Inovasi SI',
            'tingkat' => 'Nasional',
            'peringkat' => 'Juara 1',
            'tahun' => 2026,
        ])->assertRedirect(route('panel.konten', ['tab' => 'prestasi']));

        $this->actingAs($admin)->post(route('panel.konten.berita.store'), [
            'title' => 'Rilis Portal Satu Data Versi Produksi',
            'badge' => 'Akademik',
            'corner' => 'Terbaru',
            'date_label' => '20 Mei 2026',
            'author' => 'Admin Prodi',
            'read_minutes' => 3,
            'image_url' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800',
            'excerpt' => 'Ringkasan peluncuran portal terbaru.',
            'body_text' => "Paragraf pertama peluncuran.\n\nParagraf kedua penjelasan.",
        ])->assertRedirect(route('panel.konten', ['tab' => 'berita']));

        $this->actingAs($admin)->post(route('panel.konten.kurikulum.store'), [
            'kode' => 'SI-999',
            'nama' => 'Tata Kelola Satu Data Lanjut',
            'tahun' => 2026,
            'semester' => 6,
            'sks' => 3,
            'pilihan' => '1',
        ])->assertRedirect(route('panel.konten', ['tab' => 'kurikulum']));

        $this->post(route('logout'));

        $this->get('/')->assertStatus(200)
            ->assertSee('Rilis Portal Satu Data Versi Produksi', false)
            ->assertSee('Dr. Uji Pengampu, S.Kom., M.T.', false)
            ->assertSee('Juara 1 Hackathon Satu Data 2026', false)
            ->assertSee('Tata Kelola Satu Data Lanjut', false);

        $this->get('/berita')->assertStatus(200)
            ->assertSee('Rilis Portal Satu Data Versi Produksi', false);

        $this->get('/tentang-kami')->assertStatus(200)
            ->assertSee('Unggul BAN-PT 2026', false)
            ->assertSee('Deskripsi program studi yang telah diperbarui.', false);
    }

    public function test_dppl_17_tables_and_workflow_columns_exist(): void
    {
        $this->assertSame(83.33, ChartPaletteService::parseNumeric('83.33'));
        $this->assertSame(1250.5, ChartPaletteService::parseNumeric('1.250,50'));
        $this->assertSame(1250000.0, ChartPaletteService::parseNumeric('1.250.000'));

        foreach ([
            'peran',
            'hak_akses',
            'peran_hak_akses',
            'pengguna',
            'profil_prodi',
            'berita',
            'kurikulum',
            'dosen',
            'prestasi',
            'kategori',
            'formulir',
            'kolom_formulir',
            'pengisian_formulir',
            'data_isian',
            'dataset',
            'permohonan_data',
            'visualisasi_dashboard',
        ] as $dpplTable) {
            $this->assertTrue(Schema::hasTable($dpplTable), "Tabel DPPL {$dpplTable} wajib ada.");
        }

        $this->assertTrue(Schema::hasColumns('formulir', ['batasi_satu_respon', 'status_preprocessing', 'ringkasan_preprocessing', 'diproses_oleh', 'waktu_diproses']));
        $this->assertTrue(Schema::hasColumn('kolom_formulir', 'deskripsi_bantuan'));
        $this->assertTrue(Schema::hasColumns('pengisian_formulir', ['status_validasi', 'catatan_validasi', 'sumber_input']));
        $this->assertTrue(Schema::hasColumn('data_isian', 'nilai_mentah'));
        $this->assertTrue(Schema::hasColumns('dataset', ['status_publikasi', 'konfigurasi_sumber']));

        $admin = User::query()->where('nim', 'admin')->firstOrFail();
        $this->actingAs($admin)->post(route('panel.reset-demo'))
            ->assertRedirect(route('panel.ringkasan'))
            ->assertSessionHas('success');
    }

    public function test_full_page_gform_studio_routes_templates_duplicate_and_media_upload(): void
    {
        Storage::fake('local');

        $wali = User::query()->where('nim', 'walidata')->firstOrFail();
        $admin = User::query()->where('nim', 'admin')->firstOrFail();
        $produsen = User::query()->where('nim', '1462200031')->firstOrFail();
        $category = Category::query()->firstOrFail();

        // Gallery page uses dedicated new pages instead of a create/edit popup modal
        $this->actingAs($wali)->get(route('panel.formulir'))
            ->assertStatus(200)
            ->assertSee(route('panel.formulir.create'), false)
            ->assertDontSee('form-builder-title', false);

        // Full-page Create Studio + Template Support
        $this->actingAs($wali)->get(route('panel.formulir.create'))
            ->assertStatus(200)
            ->assertSee('gform-studio-data', false);

        $this->actingAs($wali)->get(route('panel.formulir.create', ['template' => 'survei-likert']))
            ->assertStatus(200)
            ->assertSee('Survei Kepuasan Layanan Prodi Sistem Informasi', false);

        $this->actingAs($admin)->get(route('panel.formulir.create'))
            ->assertStatus(200);

        $this->actingAs($produsen)->get(route('panel.formulir.create'))
            ->assertStatus(403);

        // Upload & serve media image safely
        $img = UploadedFile::fake()->image('banner-unusa.png', 1200, 300);
        $uploadRes = $this->actingAs($wali)->postJson(route('panel.formulir.media.upload'), [
            'image' => $img,
        ]);
        $uploadRes->assertStatus(201);
        $mediaPath = (string) $uploadRes->json('path');
        $this->assertNotEmpty($mediaPath);
        Storage::disk('local')->assertExists($mediaPath);

        $this->get(route('formulir.media', ['path' => $mediaPath]))
            ->assertStatus(200);

        $this->get('/formulir-media/../../.env')
            ->assertStatus(404);

        // Create a form and test full-page Edit Studio & Duplicate
        $form = DataForm::create([
            'code' => 'F-STUDIO-01',
            'id_kategori' => $category->id,
            'judul' => 'Instrumen Studio Full Page',
            'deskripsi' => 'Uji halaman ubah khusus',
            'tahun' => 2026,
            'periode' => 'Ganjil',
            'target' => 'Mahasiswa',
            'tanggal_waktu' => now()->addDays(10),
            'status_aktif' => true,
            'pengaturan' => [
                'theme' => ['palette' => 'benhur', 'header_preset' => 'benhur-arch'],
                'is_quiz' => true,
            ],
            'dibuat_oleh' => $wali->id,
        ]);
        $form->syncFields([
            [
                'id' => 'skala_1',
                'label' => 'Penilaian Layanan',
                'type' => 'linear_scale',
                'required' => true,
                'config' => ['scale_min' => 1, 'scale_max' => 5, 'scale_min_label' => 'Kurang', 'scale_max_label' => 'Sangat Baik'],
            ],
        ]);

        $this->actingAs($wali)->get(route('panel.formulir.edit', $form->code))
            ->assertStatus(200)
            ->assertSee('Instrumen Studio Full Page', false);

        $this->actingAs($produsen)->get(route('panel.formulir.edit', $form->code))
            ->assertStatus(403);

        $dupRes = $this->actingAs($wali)->post(route('panel.formulir.duplicate', $form->code));
        $cloned = DataForm::query()->where('judul', 'Instrumen Studio Full Page (Salinan)')->firstOrFail();
        $dupRes->assertRedirect(route('panel.formulir.edit', $cloned->code));
        $this->assertSame('benhur', $cloned->settings['theme']['palette'] ?? null);
        $this->assertSame(1, $cloned->kolomFormulir()->count());
    }

    public function test_full_gform_types_validation_branching_quiz_preprocessing_and_dataset_compatibility(): void
    {
        $wali = User::query()->where('nim', 'walidata')->firstOrFail();
        $mhs1 = User::query()->where('nim', '1462200031')->firstOrFail();
        $mhs2 = User::query()->where('nim', '1462300012')->firstOrFail();
        $category = Category::query()->firstOrFail();

        $fieldsPayload = [
            [
                'id' => 'sec_1',
                'label' => 'Bagian 1: Profil & Evaluasi',
                'help' => 'Bagian awal survei',
                'type' => 'section',
                'required' => false,
                'config' => ['after_action' => 'next'],
            ],
            [
                'id' => 'blk_info',
                'label' => 'Petunjuk Umum',
                'help' => 'Isi seluruh pertanyaan dengan jujur.',
                'type' => 'title_block',
                'required' => false,
                'config' => [],
            ],
            [
                'id' => 'blk_vid',
                'label' => 'Video Panduan',
                'help' => '',
                'type' => 'video_block',
                'required' => false,
                'config' => ['video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'video_caption' => 'Panduan'],
            ],
            [
                'id' => 'email_mhs',
                'label' => 'Email Aktif',
                'help' => '',
                'type' => 'text',
                'required' => true,
                'config' => [
                    'validation' => ['enabled' => true, 'category' => 'text', 'operator' => 'email', 'message' => 'Email harus valid.'],
                    'points' => 20,
                    'answer_key' => 'budi@student.unusa.ac.id',
                ],
            ],
            [
                'id' => 'jalur',
                'label' => 'Jalur',
                'help' => '',
                'type' => 'radio',
                'required' => true,
                'options' => ['Reguler', 'Prestasi'],
                'config' => [
                    'allow_other' => true,
                    'use_branching' => true,
                    'branching' => ['Reguler' => 'sec_end'],
                    'points' => 30,
                    'answer_key' => 'Prestasi',
                ],
            ],
            [
                'id' => 'kepuasan',
                'label' => 'Skala Kepuasan',
                'help' => '',
                'type' => 'linear_scale',
                'required' => true,
                'config' => ['scale_min' => 1, 'scale_max' => 5, 'scale_min_label' => 'Rendah', 'scale_max_label' => 'Tinggi'],
            ],
            [
                'id' => 'bintang',
                'label' => 'Rating Fasilitas',
                'help' => '',
                'type' => 'rating',
                'required' => true,
                'config' => ['rating_max' => 5, 'rating_icon' => 'star'],
            ],
            [
                'id' => 'kisi_mc',
                'label' => 'Kisi Evaluasi',
                'help' => '',
                'type' => 'mc_grid',
                'required' => true,
                'config' => [
                    'grid_rows' => ['Dosen', 'Lab'],
                    'grid_cols' => ['Cukup', 'Baik'],
                    'require_row_response' => true,
                    'limit_one_per_col' => true,
                ],
            ],
            [
                'id' => 'jam_kegiatan',
                'label' => 'Jam Konsultasi',
                'help' => '',
                'type' => 'time',
                'required' => true,
                'config' => ['time_mode' => 'time'],
            ],
            [
                'id' => 'sec_skip',
                'label' => 'Bagian 2: Khusus Prestasi',
                'help' => '',
                'type' => 'section',
                'required' => false,
                'config' => ['after_action' => 'next'],
            ],
            [
                'id' => 'nama_lomba',
                'label' => 'Nama Lomba Wajib',
                'help' => '',
                'type' => 'text',
                'required' => true,
                'config' => [],
            ],
            [
                'id' => 'sec_end',
                'label' => 'Bagian 3: Penutup',
                'help' => '',
                'type' => 'section',
                'required' => false,
                'config' => ['after_action' => 'submit'],
            ],
            [
                'id' => 'catatan',
                'label' => 'Catatan Tambahan',
                'help' => '',
                'type' => 'textarea',
                'required' => false,
                'config' => [],
            ],
        ];

        $settingsPayload = [
            'theme' => ['palette' => 'cyan', 'header_preset' => 'cyan-wave'],
            'is_quiz' => true,
            'release_grade' => 'immediately',
            'show_progress_bar' => true,
            'confirmation_message' => 'Terima kasih sudah mengisi survei UNUSA!',
        ];

        $this->actingAs($wali)->post(route('panel.formulir.store'), [
            'title' => 'Survei Lengkap GForm Full Asset',
            'description' => 'Semua fitur GForm',
            'category_id' => $category->id,
            'tahun' => '2026',
            'periode' => 'Ganjil',
            'target' => 'Mahasiswa',
            'batasi_satu_respon' => '0',
            'deadline' => now()->addDays(10)->format('Y-m-d\TH:i'),
            'fields_json' => json_encode($fieldsPayload, JSON_THROW_ON_ERROR),
            'settings_json' => json_encode($settingsPayload, JSON_THROW_ON_ERROR),
        ])->assertRedirect(route('panel.formulir'))
            ->assertSessionHas('success', 'Formulir Berhasil Dibuat');

        $form = DataForm::query()->where('judul', 'Survei Lengkap GForm Full Asset')->firstOrFail();

        // Non-input blocks exist in ->items, excluded from ->fields and ->sourceCols()
        $this->assertCount(13, $form->items);
        $this->assertCount(8, $form->fields);
        $this->assertCount(11, $form->sourceCols()); // 3 meta + 8 input columns

        // Respondent page renders all sections, videos, grids, scale, rating
        $this->actingAs($mhs1)->get(route('formulir.show', $form->code))
            ->assertStatus(200)
            ->assertSee('Skala Kepuasan', false)
            ->assertSee('Kisi Evaluasi', false)
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);

        // Invalid custom email validation + grid column uniqueness rejection
        $this->actingAs($mhs1)->postJson(route('panel.isi.submit', $form->code), [
            'values' => [
                'email_mhs' => 'bukan-email',
                'jalur' => 'Reguler',
                'kepuasan' => '5',
                'bintang' => '4',
                'kisi_mc' => [0 => 'Baik', 1 => 'Baik'],
                'jam_kegiatan' => '09:30',
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email_mhs', 'kisi_mc']);

        // Valid branched submission: chooses 'Reguler' -> skips 'sec_skip' ('nama_lomba' required is bypassed!)
        $validRes = $this->actingAs($mhs1)->post(route('panel.isi.submit', $form->code), [
            'values' => [
                'email_mhs' => 'budi@student.unusa.ac.id',
                'jalur' => 'Reguler',
                'kepuasan' => '5',
                'bintang' => '4',
                'kisi_mc' => [0 => 'Baik', 1 => 'Cukup'],
                'jam_kegiatan' => '09:30',
                'catatan' => 'Sangat membantu',
            ],
        ]);
        $validRes->assertRedirect();
        $validRes->assertSessionHas('quiz_result');
        $quizResult = session('quiz_result');
        $this->assertSame(20, $quizResult['earned']);
        $this->assertSame(50, $quizResult['possible']);

        // Second submission with 'allow_other' custom value & visiting skipped section
        $this->actingAs($mhs2)->postJson(route('panel.isi.submit', $form->code), [
            'values' => [
                'email_mhs' => 'siti@student.unusa.ac.id',
                'jalur' => '__other__',
                'kepuasan' => '4',
                'bintang' => '5',
                'kisi_mc' => [0 => 'Cukup', 1 => 'Baik'],
                'jam_kegiatan' => '14:00',
                'nama_lomba' => 'Gemastik 2026',
            ],
            'values_other' => [
                'jalur' => 'Jalur Mandiri Inovasi',
            ],
        ])->assertStatus(201);

        // Close form then Pre-processing finalization + Dataset Rekap AVG on linear_scale passes cleanly!
        $form->update(['status_aktif' => false]);
        $this->actingAs($wali)->post(route('panel.preprocessing.finalize', $form->code))
            ->assertRedirect(route('panel.preprocessing', ['form' => $form->code]))
            ->assertSessionHas('success');
        $this->assertTrue($form->fresh()->isPreprocessed());

        $this->actingAs($wali)->post(route('panel.dataset.from-form'), [
            'form_id' => $form->id,
            'mode' => 'rekap',
            'group_field_id' => 'jalur',
            'agg_func' => 'avg',
            'agg_field_id' => 'kepuasan',
            'title' => 'Rekap Rata-rata Kepuasan Jalur 2026',
            'category_id' => $category->id,
            'tahun' => '2026',
            'periode' => 'Ganjil',
            'access' => 'public',
            'publish_status' => 'terpublikasi',
            'format' => 'CSV',
        ])->assertRedirect(route('panel.dataset'))
            ->assertSessionHas('success');

        $ds = Dataset::query()->where('judul', 'Rekap Rata-rata Kepuasan Jalur 2026')->firstOrFail();
        $assoc = collect($ds->baris)->mapWithKeys(fn (array $r): array => [(string) $r[0] => $r[1]])->all();
        $this->assertSame(5, $assoc['Reguler']);
        $this->assertSame(4, $assoc['Jalur Mandiri Inovasi']);
    }

    public function test_unrestricted_and_chunked_large_file_uploads_with_security_blocklist(): void
    {
        Storage::fake('local');

        $wali = User::query()->where('nim', 'walidata')->firstOrFail();
        $mhs1 = User::query()->where('nim', '1462200031')->firstOrFail();
        $category = Category::query()->firstOrFail();

        // 1. Create form with unrestricted file type & unlimited file size (file_max_mb = 0)
        $this->actingAs($wali)->post(route('panel.formulir.store'), [
            'title' => 'Unggahan Bebas Jenis & Ukuran 2026',
            'description' => 'Mendukung DOCX, ZIP, MP4, PDF tanpa batas PHP 2M',
            'category_id' => $category->id,
            'tahun' => '2026',
            'periode' => 'Ganjil',
            'target' => 'Mahasiswa',
            'deadline' => now()->addDays(7)->format('Y-m-d\TH:i'),
            'fields_json' => json_encode([
                [
                    'id' => 'dokumen',
                    'label' => 'Berkas Bebas',
                    'type' => 'file',
                    'required' => true,
                    'config' => [
                        'restrict_file_types' => false,
                        'file_max_count' => 1,
                        'file_max_mb' => 0,
                    ],
                ],
                [
                    'id' => 'khusus_pdf',
                    'label' => 'Khusus PDF',
                    'type' => 'file',
                    'required' => false,
                    'config' => [
                        'restrict_file_types' => true,
                        'file_types' => ['pdf'],
                        'file_max_count' => 1,
                        'file_max_mb' => 25,
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ])->assertRedirect(route('panel.formulir'));

        $form = DataForm::query()->where('judul', 'Unggahan Bebas Jenis & Ukuran 2026')->firstOrFail();
        $this->actingAs($mhs1)->get(route('formulir.show', $form->code))
            ->assertStatus(200)
            ->assertSee('Semua jenis berkas', false)
            ->assertSee('tanpa batas ukuran', false);

        // 2. Reject dangerous executable script extension (.php)
        $this->actingAs($mhs1)->call(
            'POST',
            route('panel.isi.upload-chunk', $form->code),
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_FIELD_ID' => 'dokumen',
                'HTTP_X_UPLOAD_ID' => 'bad_php_1',
                'HTTP_X_CHUNK_INDEX' => '0',
                'HTTP_X_TOTAL_CHUNKS' => '1',
                'HTTP_X_FILE_NAME' => rawurlencode('exploit.php'),
            ],
            '<?php echo 1;'
        )->assertStatus(422);

        // 3. Reject disallowed format (.zip) on PDF-restricted field
        $this->actingAs($mhs1)->call(
            'POST',
            route('panel.isi.upload-chunk', $form->code),
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_FIELD_ID' => 'khusus_pdf',
                'HTTP_X_UPLOAD_ID' => 'bad_zip_1',
                'HTTP_X_CHUNK_INDEX' => '0',
                'HTTP_X_TOTAL_CHUNKS' => '1',
                'HTTP_X_FILE_NAME' => rawurlencode('archive.zip'),
            ],
            'PKZIPCONTENT'
        )->assertStatus(422);

        // 4. Multi-chunk binary upload for a .zip archive (>2 chunks assembled cleanly)
        $part1 = str_repeat('A', 600000);
        $part2 = str_repeat('B', 600000);

        $chunkRes1 = $this->actingAs($mhs1)->call(
            'POST',
            route('panel.isi.upload-chunk', $form->code),
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_FIELD_ID' => 'dokumen',
                'HTTP_X_UPLOAD_ID' => 'zip_multi_1',
                'HTTP_X_CHUNK_INDEX' => '0',
                'HTTP_X_TOTAL_CHUNKS' => '2',
                'HTTP_X_FILE_NAME' => rawurlencode('laporan-proyek.zip'),
            ],
            $part1
        );
        $chunkRes1->assertStatus(200)->assertJsonPath('completed', false);

        $chunkRes2 = $this->actingAs($mhs1)->call(
            'POST',
            route('panel.isi.upload-chunk', $form->code),
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_FIELD_ID' => 'dokumen',
                'HTTP_X_UPLOAD_ID' => 'zip_multi_1',
                'HTTP_X_CHUNK_INDEX' => '1',
                'HTTP_X_TOTAL_CHUNKS' => '2',
                'HTTP_X_FILE_NAME' => rawurlencode('laporan-proyek.zip'),
            ],
            $part2
        );
        $chunkRes2->assertStatus(201)->assertJsonPath('completed', true);
        $token = (string) $chunkRes2->json('token');
        $this->assertNotEmpty($token);

        // Submit form with chunk token (no raw multipart file required)
        $this->actingAs($mhs1)->postJson(route('panel.isi.submit', $form->code), [
            'uploaded_tokens' => [
                'dokumen' => $token,
            ],
        ])->assertStatus(201);

        $sub = FormSubmission::query()->where('id_formulir', $form->id)->latest('id')->firstOrFail();
        $this->assertSame('laporan-proyek.zip', $sub->values['dokumen'] ?? '');
        $storedPath = $sub->attachments['dokumen'] ?? '';
        $this->assertNotEmpty($storedPath);
        $this->assertSame($part1.$part2, Storage::disk('local')->get($storedPath));

        // Download & direct multipart fallback work while open; closing unlocks pre-processing finalize
        $this->actingAs($mhs1)->get(route('panel.submissions.file', [$sub->code, 'dokumen']))->assertStatus(200);

        $docx = UploadedFile::fake()->create('artikel.docx', 200, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->actingAs($mhs1)->postJson(route('panel.isi.submit', $form->code), [
            'files' => ['dokumen' => $docx],
        ])->assertStatus(201);

        $form->update(['status_aktif' => false]);
        $this->actingAs($wali)->post(route('panel.preprocessing.finalize', $form->code))
            ->assertRedirect(route('panel.preprocessing', ['form' => $form->code]))
            ->assertSessionHas('success');
        $this->assertTrue($form->fresh()->isPreprocessed());

        // 6. Chunked Studio image media upload succeeds
        $mediaChunk = $this->actingAs($wali)->call(
            'POST',
            route('panel.formulir.media.upload'),
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_UPLOAD_ID' => 'media_banner_1',
                'HTTP_X_CHUNK_INDEX' => '0',
                'HTTP_X_TOTAL_CHUNKS' => '1',
                'HTTP_X_FILE_NAME' => rawurlencode('cover-besar.png'),
            ],
            'PNGDATA'
        );
        $mediaChunk->assertStatus(201)->assertJsonPath('completed', true);
        $this->get(route('formulir.media', ['path' => (string) $mediaChunk->json('path')]))->assertStatus(200);
    }

    public function test_formdata_chunked_upload_zero_byte_and_empty_token_guards_and_full_page_submission_editor(): void
    {
        Storage::fake('local');

        $wali = User::query()->where('nim', 'walidata')->firstOrFail();
        $admin = User::query()->where('nim', 'admin')->firstOrFail();
        $mhs = User::query()->where('nim', '1462200031')->firstOrFail();
        $category = Category::query()->firstOrFail();

        $form = DataForm::create([
            'code' => 'F-PROOF-FIX',
            'id_kategori' => $category->id,
            'judul' => 'Prestasi Mahasiswa Uji Bukti',
            'deskripsi' => 'Uji unggahan bukti dan perbaikan halaman penuh',
            'tahun' => 2026,
            'periode' => 'Ganjil',
            'target' => 'Mahasiswa',
            'tanggal_waktu' => now()->addDays(7),
            'status_aktif' => true,
            'dibuat_oleh' => $wali->id,
        ]);
        $form->syncFields([
            ['id' => 'judul_kegiatan', 'label' => 'Nama Kompetisi', 'type' => 'text', 'required' => true],
            ['id' => 'tingkat', 'label' => 'Tingkat Capaian', 'type' => 'radio', 'required' => true, 'options' => ['Kota / Provinsi', 'Nasional', 'Internasional']],
            ['id' => 'poin', 'label' => 'Jumlah Peserta', 'type' => 'number', 'required' => true],
            ['id' => 'bukti', 'label' => 'Unggah Sertifikat / Surat Tugas', 'type' => 'file', 'required' => true, 'config' => ['restrict_file_types' => false, 'file_max_mb' => 0, 'file_max_count' => 1]],
        ]);

        // 1. Reject 0-byte chunk on public submission upload-chunk endpoint
        $emptyChunk = UploadedFile::fake()->createWithContent('empty.pdf', '');
        $this->actingAs($mhs)->postJson(route('panel.isi.upload-chunk', $form->code), [
            'field_id' => 'bukti',
            'upload_id' => 'zero_test_1',
            'chunk_index' => '0',
            'total_chunks' => '1',
            'file_name' => 'empty.pdf',
            'chunk' => $emptyChunk,
        ])->assertStatus(422);

        // 2. Reject empty "[]" token array (must NEVER bypass required proof check!)
        $this->actingAs($mhs)->postJson(route('panel.isi.submit', $form->code), [
            'values' => [
                'judul_kegiatan' => 'Lomba Essay Nasional',
                'tingkat' => 'Nasional',
                'poin' => '3',
            ],
            'uploaded_tokens' => [
                'bukti' => '[]',
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['bukti']);

        // 3. Multi-chunk FormData upload assembles full real bytes cleanly
        $partA = '%PDF-1.4 '.str_repeat('X', 500000);
        $partB = str_repeat('Y', 400000).'%%EOF';
        $chunkFileA = UploadedFile::fake()->createWithContent('chunk.bin', $partA);
        $chunkFileB = UploadedFile::fake()->createWithContent('chunk.bin', $partB);

        $this->actingAs($mhs)->postJson(route('panel.isi.upload-chunk', $form->code), [
            'field_id' => 'bukti',
            'upload_id' => 'fd_pdf_1',
            'chunk_index' => '0',
            'total_chunks' => '2',
            'file_name' => 'sertifikat-riset.pdf',
            'chunk' => $chunkFileA,
        ])->assertStatus(200)->assertJsonPath('completed', false);

        $chunkDone = $this->actingAs($mhs)->postJson(route('panel.isi.upload-chunk', $form->code), [
            'field_id' => 'bukti',
            'upload_id' => 'fd_pdf_1',
            'chunk_index' => '1',
            'total_chunks' => '2',
            'file_name' => 'sertifikat-riset.pdf',
            'chunk' => $chunkFileB,
        ]);
        $chunkDone->assertStatus(201)->assertJsonPath('completed', true);
        $token = (string) $chunkDone->json('token');
        $this->assertNotEmpty($token);

        $this->actingAs($mhs)->postJson(route('panel.isi.submit', $form->code), [
            'values' => [
                'judul_kegiatan' => 'Lomba Essay Nasional',
                'tingkat' => 'Nasional',
                'poin' => '3',
            ],
            'uploaded_tokens' => [
                'bukti' => $token,
            ],
        ])->assertStatus(201);

        $sub1 = FormSubmission::query()->where('id_formulir', $form->id)->latest('id')->firstOrFail();
        $this->assertSame('sertifikat-riset.pdf', $sub1->values['bukti'] ?? '');
        $storedPath1 = $sub1->attachments['bukti'] ?? '';
        $this->assertNotEmpty($storedPath1);
        $this->assertSame($partA.$partB, Storage::disk('local')->get($storedPath1));
        $this->assertSame($storedPath1, $sub1->jalur_berkas_bukti);

        // Both download and inline preview routes work
        $this->actingAs($wali)->get(route('panel.submissions.file', [$sub1->code, 'bukti']))->assertStatus(200);
        $this->actingAs($wali)->get(route('panel.submissions.file', [$sub1->code, 'bukti', 'inline' => 1]))->assertStatus(200);

        // Close form before entering Pre-processing Data workbench
        $form->update(['status_aktif' => false]);

        // 4. Pre-processing table shows attachment links & edit page link, with NO clean-modal popup
        $this->actingAs($wali)->get(route('panel.preprocessing', ['form' => $form->code]))
            ->assertStatus(200)
            ->assertDontSee('clean-modal-title', false)
            ->assertSee(route('panel.preprocessing.submissions.edit', $sub1->code), false)
            ->assertSee(route('panel.submissions.file', [$sub1->code, 'bukti']), false)
            ->assertSee('sertifikat-riset.pdf', false);

        // 5. Legacy/imported broken row missing proof (bukti = "") -> fixed on dedicated full page
        $brokenSub = FormSubmission::create([
            'code' => 'S-BROKEN-FIX',
            'id_formulir' => $form->id,
            'diisi_oleh' => $mhs->id,
            'waktu_pengisian' => now(),
            'status_validasi' => 'mentah',
            'sumber_input' => 'formulir',
            'jalur_berkas_bukti' => null,
        ]);
        $brokenSub->syncValues($form, [
            'judul_kegiatan' => 'lomba   karya   tulis',
            'tingkat' => 'Nasional',
            'poin' => '2',
            'bukti' => '',
        ], null, null, false);

        // RBAC & pre-selected value rendering on dedicated full page
        $this->actingAs($mhs)->get(route('panel.preprocessing.submissions.edit', $brokenSub->code))
            ->assertStatus(403);
        $this->actingAs($admin)->get(route('panel.preprocessing.submissions.edit', $brokenSub->code))
            ->assertStatus(200);
        $this->actingAs($wali)->get(route('panel.preprocessing.submissions.edit', $brokenSub->code))
            ->assertStatus(200)
            ->assertSee('Perbaiki Data Isian', false)
            ->assertSee('<option value="Nasional" selected>Nasional</option>', false)
            ->assertSee(route('panel.data.submissions.update', $brokenSub->code), false)
            ->assertSee(json_encode(route('panel.preprocessing.submissions.upload-chunk', $brokenSub->code)), false);

        // 0-byte chunk also rejected on preprocessing upload-chunk endpoint
        $this->actingAs($wali)->postJson(route('panel.preprocessing.submissions.upload-chunk', $brokenSub->code), [
            'field_id' => 'bukti',
            'upload_id' => 'fix_zero_1',
            'chunk_index' => '0',
            'total_chunks' => '1',
            'file_name' => 'empty.pdf',
            'chunk' => $emptyChunk,
        ])->assertStatus(422);

        // Wali Data uploads replacement proof in FormData chunks & saves full page
        $fixBytes = '%PDF-1.4 VALID-PROOF-BYTES '.str_repeat('Z', 30000).'%%EOF';
        $fixChunk = UploadedFile::fake()->createWithContent('chunk.bin', $fixBytes);
        $fixChunkRes = $this->actingAs($wali)->postJson(route('panel.preprocessing.submissions.upload-chunk', $brokenSub->code), [
            'field_id' => 'bukti',
            'upload_id' => 'fix_ok_1',
            'chunk_index' => '0',
            'total_chunks' => '1',
            'file_name' => 'bukti-perbaikan.pdf',
            'chunk' => $fixChunk,
        ]);
        $fixChunkRes->assertStatus(201)->assertJsonPath('completed', true);
        $fixToken = (string) $fixChunkRes->json('token');

        $this->actingAs($wali)->put(route('panel.data.submissions.update', $brokenSub->code), [
            'values' => [
                'judul_kegiatan' => 'Lomba Karya Tulis',
                'tingkat' => 'Nasional',
                'poin' => '2',
            ],
            'uploaded_tokens' => [
                'bukti' => $fixToken,
            ],
        ])->assertRedirect(route('panel.preprocessing', ['form' => $form->code]))
            ->assertSessionHas('success');

        $brokenSub->refresh();
        $this->assertSame('Lomba Karya Tulis', $brokenSub->values['judul_kegiatan']);
        $this->assertSame('lomba   karya   tulis', $brokenSub->rawValues['judul_kegiatan']);
        $this->assertSame('bukti-perbaikan.pdf', $brokenSub->values['bukti']);
        $fixedPath = $brokenSub->attachments['bukti'] ?? '';
        $this->assertNotEmpty($fixedPath);
        $this->assertSame($fixBytes, Storage::disk('local')->get($fixedPath));
        $this->assertSame($fixedPath, $brokenSub->jalur_berkas_bukti);

        // Finalize preprocessing now passes!
        $this->actingAs($wali)->post(route('panel.preprocessing.finalize', $form->code))
            ->assertRedirect(route('panel.preprocessing', ['form' => $form->code]))
            ->assertSessionHas('success');
        $this->assertTrue($form->fresh()->isPreprocessed());
    }

    public function test_preprocessing_directory_front_page_search_filters_and_dedicated_form_workbench(): void
    {
        $wali = User::query()->where('nim', 'walidata')->firstOrFail();
        $admin = User::query()->where('nim', 'admin')->firstOrFail();
        $mhs = User::query()->where('nim', '1462200031')->firstOrFail();
        $cat1 = Category::query()->orderBy('id')->firstOrFail();
        $cat2 = Category::query()->orderByDesc('id')->firstOrFail();

        $formQueue = DataForm::create([
            'code' => 'F-DIR-QUEUE',
            'id_kategori' => $cat1->id,
            'judul' => 'Pendataan Riset Antrean 2026',
            'deskripsi' => 'Butuh pembersihan',
            'tahun' => 2026,
            'periode' => 'Ganjil',
            'target' => 'Mahasiswa',
            'tanggal_waktu' => now()->addDays(5),
            'status_aktif' => false,
            'status_preprocessing' => 'proses',
            'dibuat_oleh' => $wali->id,
        ]);
        $formQueue->syncFields([['id' => 'judul', 'label' => 'Judul', 'type' => 'text', 'required' => true]]);
        FormSubmission::create([
            'code' => 'S-DIR-Q1',
            'id_formulir' => $formQueue->id,
            'diisi_oleh' => $mhs->id,
            'waktu_pengisian' => now(),
            'status_validasi' => 'mentah',
            'sumber_input' => 'formulir',
        ])->syncValues($formQueue, ['judul' => 'Mini Riset A']);

        $formReady = DataForm::create([
            'code' => 'F-DIR-READY',
            'id_kategori' => $cat2->id,
            'judul' => 'Survei Alumni Terverifikasi 2025',
            'deskripsi' => 'Sudah siap dataset',
            'tahun' => 2025,
            'periode' => 'Genap',
            'target' => 'Semua',
            'tanggal_waktu' => now()->subDay(),
            'status_aktif' => true,
            'status_preprocessing' => 'siap',
            'dibuat_oleh' => $wali->id,
        ]);
        $formReady->syncFields([['id' => 'kerja', 'label' => 'Status', 'type' => 'text', 'required' => true]]);
        FormSubmission::create([
            'code' => 'S-DIR-R1',
            'id_formulir' => $formReady->id,
            'diisi_oleh' => $mhs->id,
            'waktu_pengisian' => now(),
            'status_validasi' => 'valid',
            'sumber_input' => 'formulir',
        ])->syncValues($formReady, ['kerja' => 'Bekerja']);

        $formEmpty = DataForm::create([
            'code' => 'F-DIR-EMPTY',
            'id_kategori' => $cat1->id,
            'judul' => 'Kuesioner Lab Kosong 2024',
            'deskripsi' => 'Belum ada responden',
            'tahun' => 2024,
            'periode' => 'Ganjil',
            'target' => 'Dosen',
            'tanggal_waktu' => now()->addDays(5),
            'status_aktif' => false,
            'status_preprocessing' => 'belum',
            'dibuat_oleh' => $wali->id,
        ]);
        $formEmpty->syncFields([['id' => 'lab', 'label' => 'Lab', 'type' => 'text', 'required' => true]]);

        $formStillOpen = DataForm::create([
            'code' => 'F-DIR-OPEN',
            'id_kategori' => $cat1->id,
            'judul' => 'Survei Aktif Masih Terbuka',
            'deskripsi' => 'Belum boleh masuk preprocessing',
            'tahun' => 2026,
            'periode' => 'Ganjil',
            'target' => 'Mahasiswa',
            'tanggal_waktu' => now()->addDays(5),
            'status_aktif' => true,
            'status_preprocessing' => 'proses',
            'dibuat_oleh' => $wali->id,
        ]);
        $formStillOpen->syncFields([['id' => 'opsi', 'label' => 'Opsi', 'type' => 'text', 'required' => true]]);

        // 1. /panel/preprocessing displays closed-form directory without <select name="form"> and excludes open forms
        $this->actingAs($wali)->get(route('panel.preprocessing'))
            ->assertStatus(200)
            ->assertSee('Pilih Formulir Pre-processing', false)
            ->assertSee('Pendataan Riset Antrean 2026', false)
            ->assertSee('Survei Alumni Terverifikasi 2025', false)
            ->assertSee('Kuesioner Lab Kosong 2024', false)
            ->assertDontSee('Survei Aktif Masih Terbuka', false)
            ->assertSee(route('panel.preprocessing.show', $formQueue->code), false)
            ->assertSee(route('panel.preprocessing.show', $formReady->code), false)
            ->assertDontSee('name="form"', false)
            ->assertDontSee('&amp;amp;', false);

        $this->actingAs($wali)->get(route('panel.preprocessing.show', $formStillOpen->code))
            ->assertRedirect(route('panel.preprocessing'))
            ->assertSessionHas('error');

        // 2. Search & filters on front directory work accurately
        $this->actingAs($wali)->get(route('panel.preprocessing', ['q' => 'F-DIR-READY']))
            ->assertStatus(200)
            ->assertSee('Survei Alumni Terverifikasi 2025', false)
            ->assertDontSee('Pendataan Riset Antrean 2026', false)
            ->assertDontSee('Kuesioner Lab Kosong 2024', false);

        $this->actingAs($wali)->get(route('panel.preprocessing', ['stage' => 'needs_attention']))
            ->assertStatus(200)
            ->assertSee('Pendataan Riset Antrean 2026', false)
            ->assertDontSee('Survei Alumni Terverifikasi 2025', false)
            ->assertDontSee('Kuesioner Lab Kosong 2024', false);

        $this->actingAs($wali)->get(route('panel.preprocessing', ['stage' => 'ready', 'tahun' => '2025']))
            ->assertStatus(200)
            ->assertSee('Survei Alumni Terverifikasi 2025', false)
            ->assertDontSee('Pendataan Riset Antrean 2026', false);

        $this->actingAs($wali)->get(route('panel.preprocessing', ['stage' => 'empty', 'kategori' => $cat1->code]))
            ->assertStatus(200)
            ->assertSee('Kuesioner Lab Kosong 2024', false)
            ->assertDontSee('Survei Alumni Terverifikasi 2025', false);

        // 3. Dedicated form workbench /panel/preprocessing/{code}
        $this->actingAs($mhs)->get(route('panel.preprocessing.show', $formQueue->code))->assertStatus(403);
        $this->actingAs($admin)->get(route('panel.preprocessing.show', $formQueue->code))->assertStatus(200);
        $this->actingAs($wali)->get(route('panel.preprocessing.show', $formQueue->code))
            ->assertStatus(200)
            ->assertSee('Pilih Formulir Lain', false)
            ->assertSee('Pendataan Riset Antrean 2026', false)
            ->assertSee('S-DIR-Q1', false)
            ->assertDontSee('name="form"', false)
            ->assertDontSee('&amp;amp;', false);

        // 4. Legacy ?form= query compatibility still opens the workspace cleanly
        $this->actingAs($wali)->get(route('panel.preprocessing', ['form' => $formReady->code]))
            ->assertStatus(200)
            ->assertSee('Survei Alumni Terverifikasi 2025', false)
            ->assertSee('S-DIR-R1', false)
            ->assertSee('Pilih Formulir Lain', false);
    }

    public function test_json_base64_chunk_upload_works_without_php_temp_files_across_all_endpoints(): void
    {
        Storage::fake('local');

        $wali = User::query()->where('nim', 'walidata')->firstOrFail();
        $mhs = User::query()->where('nim', '1462200031')->firstOrFail();
        $category = Category::query()->firstOrFail();

        $form = DataForm::create([
            'code' => 'F-B64-TEST',
            'id_kategori' => $category->id,
            'judul' => 'Uji JSON Base64 Upload',
            'deskripsi' => 'Uji tanpa $_FILES tmp dir',
            'tahun' => 2026,
            'periode' => 'Ganjil',
            'target' => 'Mahasiswa',
            'tanggal_waktu' => now()->addDays(5),
            'status_aktif' => true,
            'dibuat_oleh' => $wali->id,
        ]);
        $form->syncFields([
            ['id' => 'judul', 'label' => 'Judul', 'type' => 'text', 'required' => true],
            ['id' => 'bukti', 'label' => 'Bukti', 'type' => 'file', 'required' => true, 'config' => ['restrict_file_types' => true, 'file_types' => ['pdf', 'image'], 'file_max_mb' => 5, 'file_max_count' => 1]],
        ]);

        // Reject invalid base64 payload
        $this->actingAs($mhs)->postJson(route('panel.isi.upload-chunk', $form->code), [
            'field_id' => 'bukti',
            'upload_id' => 'bad_b64',
            'chunk_index' => 0,
            'total_chunks' => 1,
            'file_name' => 'diagram.png',
            'chunk_b64' => '***NOT_BASE64***',
        ])->assertStatus(422);

        // Multi-chunk PNG via JSON Base64 on public submission endpoint
        $pngPart1 = "\x89PNG\r\n\x1a\n".random_bytes(1024);
        $pngPart2 = random_bytes(1024).'IEND';

        $this->actingAs($mhs)->postJson(route('panel.isi.upload-chunk', $form->code), [
            'field_id' => 'bukti',
            'upload_id' => 'ok_b64_1',
            'chunk_index' => 0,
            'total_chunks' => 2,
            'file_name' => 'Sequence-8.drawio.png',
            'chunk_b64' => base64_encode($pngPart1),
        ])->assertStatus(200)->assertJsonPath('ok', true)->assertJsonPath('completed', false);

        $done = $this->actingAs($mhs)->postJson(route('panel.isi.upload-chunk', $form->code), [
            'field_id' => 'bukti',
            'upload_id' => 'ok_b64_1',
            'chunk_index' => 1,
            'total_chunks' => 2,
            'file_name' => 'Sequence-8.drawio.png',
            'chunk_b64' => base64_encode($pngPart2),
        ]);
        $done->assertStatus(201)->assertJsonPath('ok', true)->assertJsonPath('completed', true);
        $token = (string) $done->json('token');

        $this->actingAs($mhs)->postJson(route('panel.isi.submit', $form->code), [
            'values' => ['judul' => 'Sequence Diagram'],
            'uploaded_tokens' => ['bukti' => $token],
        ])->assertStatus(201);

        $sub = FormSubmission::query()->where('id_formulir', $form->id)->latest('id')->firstOrFail();
        $this->assertSame('Sequence-8.drawio.png', $sub->values['bukti']);
        $this->assertSame($pngPart1.$pngPart2, Storage::disk('local')->get($sub->attachments['bukti']));
        $this->actingAs($mhs)->get(route('panel.submissions.file', [$sub->code, 'bukti']))->assertStatus(200);

        // Close form before editing/replacing attachments in Pre-processing Data
        $form->update(['status_aktif' => false]);

        // Replacement via JSON Base64 on preprocessing submission edit endpoint
        $repPdf = '%PDF-1.7 '.random_bytes(2048).'%%EOF';
        $repRes = $this->actingAs($wali)->postJson(route('panel.preprocessing.submissions.upload-chunk', $sub->code), [
            'field_id' => 'bukti',
            'upload_id' => 'rep_b64_1',
            'chunk_index' => 0,
            'total_chunks' => 1,
            'file_name' => 'revisi-bukti.pdf',
            'chunk_b64' => base64_encode($repPdf),
        ]);
        $repRes->assertStatus(201)->assertJsonPath('completed', true);

        $this->actingAs($wali)->put(route('panel.data.submissions.update', $sub->code), [
            'values' => ['judul' => 'Sequence Diagram'],
            'uploaded_tokens' => ['bukti' => (string) $repRes->json('token')],
        ])->assertRedirect();

        $sub->refresh();
        $this->assertSame('revisi-bukti.pdf', $sub->values['bukti']);
        $this->assertSame($repPdf, Storage::disk('local')->get($sub->attachments['bukti']));

        // Studio header/question image upload via JSON Base64
        $mediaRes = $this->actingAs($wali)->postJson(route('panel.formulir.media.upload'), [
            'upload_id' => 'media_b64_1',
            'chunk_index' => 0,
            'total_chunks' => 1,
            'file_name' => 'banner-studio.png',
            'chunk_b64' => base64_encode($pngPart1),
        ]);
        $mediaRes->assertStatus(201)->assertJsonPath('completed', true);
        $this->assertSame($pngPart1, Storage::disk('local')->get((string) $mediaRes->json('path')));
    }

    public function test_question_image_persistence_relative_media_url_and_header_field_fallback(): void
    {
        Storage::fake('local');

        $wali = User::query()->where('nim', 'walidata')->firstOrFail();
        $mhs = User::query()->where('nim', '1462200031')->firstOrFail();
        $category = Category::query()->firstOrFail();

        $pngBytes = "\x89PNG\r\n\x1a\n".random_bytes(512);
        $mediaRes = $this->actingAs($wali)->postJson(route('panel.formulir.media.upload'), [
            'upload_id' => 'qimg_1',
            'chunk_index' => 0,
            'total_chunks' => 1,
            'file_name' => 'soal-diagram.webp',
            'chunk_b64' => base64_encode($pngBytes),
        ]);
        $mediaRes->assertStatus(201)->assertJsonPath('ok', true)->assertJsonPath('completed', true);
        $relativeUrl = (string) $mediaRes->json('url');
        $this->assertStringStartsWith('/formulir-media/', $relativeUrl);
        $this->get($relativeUrl)->assertStatus(200);

        $optImgRes = $this->actingAs($wali)->postJson(route('panel.formulir.media.upload'), [
            'upload_id' => 'optimg_1',
            'chunk_index' => 0,
            'total_chunks' => 1,
            'file_name' => 'opsi-a.png',
            'chunk_b64' => base64_encode($pngBytes),
        ]);
        $optImgRes->assertStatus(201);
        $optRelativeUrl = (string) $optImgRes->json('url');
        $this->assertStringStartsWith('/formulir-media/', $optRelativeUrl);

        $form = DataForm::create([
            'code' => 'F-QIMG-TEST',
            'id_kategori' => $category->id,
            'judul' => 'Uji Gambar Pertanyaan',
            'deskripsi' => 'Verifikasi gambar pertanyaan dan fallback upload',
            'tahun' => 2026,
            'periode' => 'Ganjil',
            'target' => 'Mahasiswa',
            'tanggal_waktu' => now()->addDays(5),
            'status_aktif' => true,
            'dibuat_oleh' => $wali->id,
        ]);
        $form->syncFields([
            ['id' => 'pil', 'label' => 'Pilih Diagram', 'type' => 'radio', 'required' => true, 'options' => ['Opsi A', 'Opsi B']],
        ]);

        // Save updated form config with question image, option image, and empty restrict_file_types normalization
        $fieldsPayload = [
            [
                'id' => 'pil',
                'label' => 'Pilih Diagram',
                'type' => 'radio',
                'required' => true,
                'options' => ['Opsi A', 'Opsi B'],
                'config' => [
                    'image_url' => $relativeUrl,
                    'image_caption' => 'Diagram Alur Sistem',
                    'image_align' => 'center',
                    'option_images' => ['Opsi A' => $optRelativeUrl],
                ],
            ],
            [
                'id' => 'q_qnexd',
                'label' => 'Unggah Sertifikat',
                'type' => 'file',
                'required' => true,
                'config' => [
                    'restrict_file_types' => true,
                    'file_types' => [],
                    'file_max_count' => 1,
                    'file_max_mb' => 0,
                ],
            ],
        ];

        $this->actingAs($wali)->put(route('panel.formulir.update', $form->code), [
            'title' => 'Uji Gambar Pertanyaan',
            'description' => 'Diperbarui',
            'category_id' => $category->id,
            'tahun' => '2026',
            'periode' => 'Ganjil',
            'target' => 'Mahasiswa',
            'deadline' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'status_aktif' => '1',
            'after_save' => 'edit',
            'fields_json' => json_encode($fieldsPayload, JSON_THROW_ON_ERROR),
            'settings_json' => json_encode(DataForm::defaultSettings(), JSON_THROW_ON_ERROR),
        ])->assertRedirect(route('panel.formulir.edit', $form->code));

        $form->refresh();
        $form->load('kolomFormulir');
        $pilCol = $form->kolomFormulir->firstWhere('kode_kolom', 'pil');
        $fileCol = $form->kolomFormulir->firstWhere('kode_kolom', 'q_qnexd');

        $this->assertSame($relativeUrl, $pilCol->konfigurasi['image_url'] ?? null);
        $this->assertSame('Diagram Alur Sistem', $pilCol->konfigurasi['image_caption'] ?? null);
        $this->assertSame($optRelativeUrl, $pilCol->konfigurasi['option_images']['Opsi A'] ?? null);
        $this->assertFalse((bool) ($fileCol->konfigurasi['restrict_file_types'] ?? false));

        // Respondent page renders embedded question image, caption, and option image
        $this->actingAs($mhs)->get(route('formulir.show', $form->code))
            ->assertStatus(200)
            ->assertSee($relativeUrl, false)
            ->assertSee('Diagram Alur Sistem', false)
            ->assertSee($optRelativeUrl, false);

        // Stale field_id returns HTTP 422 with reload_required flag
        $this->actingAs($mhs)->postJson(route('panel.isi.upload-chunk', $form->code), [
            'field_id' => 'kolom_lama',
            'upload_id' => 'stale_1',
            'chunk_index' => 0,
            'total_chunks' => 1,
            'file_name' => 'bukti.png',
            'chunk_b64' => base64_encode($pngBytes),
        ])->assertStatus(422)->assertJsonPath('reload_required', true);

        // Empty body field_id falls back cleanly to X-Field-Id header
        $this->actingAs($mhs)
            ->withHeaders(['X-Field-Id' => 'q_qnexd'])
            ->postJson(route('panel.isi.upload-chunk', $form->code), [
                'field_id' => '',
                'upload_id' => 'hdr_fallback_1',
                'chunk_index' => 0,
                'total_chunks' => 1,
                'file_name' => 'sertifikat.png',
                'chunk_b64' => base64_encode($pngBytes),
            ])->assertStatus(201)->assertJsonPath('completed', true);

        // Windows ServeCommand passthrough preserves TEMP/TMP for php artisan serve uploads
        $this->assertContains('TEMP', \Illuminate\Foundation\Console\ServeCommand::$passthroughVariables);
        $this->assertContains('TMP', \Illuminate\Foundation\Console\ServeCommand::$passthroughVariables);
    }

    public function test_user_workflow_cleanup_produsen_history_closed_preprocessing_dataset_page_and_wali_warning(): void
    {
        $wali = User::query()->where('nim', 'walidata')->firstOrFail();
        $mhs = User::query()->where('nim', '1462200031')->firstOrFail();
        $category = Category::query()->firstOrFail();

        $openForm = DataForm::create([
            'code' => 'F-UX-OPEN',
            'id_kategori' => $category->id,
            'judul' => 'Pendataan Rekognisi Terbuka',
            'deskripsi' => 'Sedang berjalan',
            'tahun' => 2026,
            'periode' => 'Ganjil',
            'target' => 'Mahasiswa',
            'tanggal_waktu' => now()->addDays(5),
            'status_aktif' => true,
            'dibuat_oleh' => $wali->id,
        ]);
        $openForm->syncFields([
            ['id' => 'kegiatan', 'label' => 'Nama Kegiatan', 'type' => 'text', 'required' => true],
            ['id' => 'skala', 'label' => 'Skala', 'type' => 'select', 'required' => true, 'options' => ['Lokal', 'Nasional']],
        ]);

        // 1. Produsen submits form and views riwayat/ringkasan without status columns/badges
        $this->actingAs($mhs)->postJson(route('panel.isi.submit', $openForm->code), [
            'values' => ['kegiatan' => 'Lomba UI/UX', 'skala' => 'Nasional'],
        ])->assertStatus(201);

        $this->actingAs($mhs)->get(route('panel.riwayat'))
            ->assertStatus(200)
            ->assertSee('Pendataan Rekognisi Terbuka', false)
            ->assertDontSee('Status Pre-processing', false)
            ->assertDontSee('Menunggu Pre-processing', false)
            ->assertDontSee('Valid (Siap Dataset)', false)
            ->assertDontSee('Lompat cepat modul', false)
            ->assertDontSee('panelShell', false);

        $this->actingAs($mhs)->get(route('panel.ringkasan'))
            ->assertStatus(200)
            ->assertDontSee('Menunggu Pre-processing', false)
            ->assertDontSee('Valid (Siap Dataset)', false)
            ->assertDontSee('Ditolak', false);

        // 2. Produsen form runner does not expose Alur Satu Data box or font-mono
        $this->actingAs($mhs)->get(route('formulir.show', $openForm->code))
            ->assertStatus(200)
            ->assertDontSee('Alur Satu Data', false)
            ->assertDontSee('font-mono', false);

        // 3. Wali Data opening formulir.show sees only concise warning without Identitas Responden / Alur Satu Data / Pre-processing button
        $this->actingAs($wali)->get(route('formulir.show', $openForm->code))
            ->assertStatus(200)
            ->assertSee('Wali Data tidak diperkenankan mengisi data.', false)
            ->assertDontSee('Identitas Responden', false)
            ->assertDontSee('Alur Satu Data', false)
            ->assertDontSee('Buka Pre-processing Data', false);

        // 4. Open form cannot be preprocessed until closed (or deadline passed), nor built into a dataset
        $this->actingAs($wali)->get(route('panel.ringkasan'))
            ->assertStatus(200)
            ->assertSee('data-scene="ambient"', false)
            ->assertSee('data-motion-toggle', false)
            ->assertSee('data-scene="walidata-pipeline"', false)
            ->assertDontSee('font-mono', false)
            ->assertDontSee('Lompat cepat modul', false);

        $this->actingAs($wali)->get(route('panel.riwayat'))
            ->assertStatus(200)
            ->assertSee('data-scene="walidata-header"', false)
            ->assertSee('data-scene="ambient"', false);

        $this->actingAs($wali)->get(route('panel.preprocessing'))
            ->assertStatus(200)
            ->assertSee('data-scene="walidata-header"', false)
            ->assertSee('data-scene="ambient"', false)
            ->assertDontSee('Pendataan Rekognisi Terbuka', false);

        $this->actingAs($wali)->get(route('panel.preprocessing.show', $openForm->code))
            ->assertRedirect(route('panel.preprocessing'))
            ->assertSessionHas('error');

        $this->actingAs($wali)->patch(route('panel.formulir.toggle', $openForm->code))
            ->assertRedirect();
        $this->assertTrue($openForm->fresh()->isClosed());

        $this->actingAs($wali)->get(route('panel.preprocessing'))
            ->assertStatus(200)
            ->assertSee('Pendataan Rekognisi Terbuka', false);

        $this->actingAs($wali)->post(route('panel.preprocessing.finalize', $openForm->code))
            ->assertRedirect(route('panel.preprocessing', ['form' => $openForm->code]))
            ->assertSessionHas('success');
        $this->assertTrue($openForm->fresh()->isPreprocessed());

        // Also create a second closed form NOT yet preprocessed, and an open form: neither should be selectable on /panel/dataset/buat
        $unprocessedClosed = DataForm::create([
            'code' => 'F-UX-WAIT',
            'id_kategori' => $category->id,
            'judul' => 'Formulir Belum Pre-processing',
            'deskripsi' => 'Masih mentah',
            'tahun' => 2026,
            'periode' => 'Ganjil',
            'target' => 'Mahasiswa',
            'tanggal_waktu' => now()->subHour(),
            'status_aktif' => false,
            'status_preprocessing' => 'proses',
            'dibuat_oleh' => $wali->id,
        ]);
        $unprocessedClosed->syncFields([['id' => 'x', 'label' => 'X', 'type' => 'text', 'required' => true]]);

        // 5. Dedicated dataset creation page (/panel/dataset/buat) uses radio cards, no dropdown, no modal popup
        $this->actingAs($wali)->get(route('panel.dataset'))
            ->assertStatus(200)
            ->assertSee('data-scene="walidata-header"', false)
            ->assertSee('data-scene="ambient"', false)
            ->assertSee(route('panel.dataset.create'), false)
            ->assertDontSee('Buat dataset dari formulir siap pakai', false);

        $this->actingAs($wali)->get(route('panel.dataset.create'))
            ->assertStatus(200)
            ->assertSee('data-scene="walidata-header"', false)
            ->assertSee('data-scene="ambient"', false)
            ->assertSee('Pendataan Rekognisi Terbuka', false)
            ->assertSee('name="form_id"', false)
            ->assertSee('type="radio"', false)
            ->assertDontSee('<select name="form_id"', false)
            ->assertDontSee('Formulir Belum Pre-processing', false)
            ->assertDontSee('Lompat cepat modul', false)
            ->assertDontSee('panelShell', false);

        $this->actingAs($wali)->post(route('panel.dataset.from-form'), [
            'form_id' => $openForm->id,
            'mode' => 'rinci',
            'picked_cols' => ['kegiatan', 'skala'],
            'title' => 'Dataset Rekognisi Halaman Penuh 2026',
            'category_id' => $category->id,
            'tahun' => '2026',
            'periode' => 'Ganjil',
            'access' => 'public',
            'format' => 'CSV',
            'publish_status' => 'terpublikasi',
        ])->assertRedirect(route('panel.dataset'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('dataset', [
            'judul' => 'Dataset Rekognisi Halaman Penuh 2026',
            'status_publikasi' => 'terpublikasi',
        ]);
    }
}
