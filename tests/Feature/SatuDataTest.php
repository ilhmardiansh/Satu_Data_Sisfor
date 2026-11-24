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
}
