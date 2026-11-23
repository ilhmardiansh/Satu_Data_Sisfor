<?php

namespace Tests\Feature;

use App\Models\AmiBenchmark;
use App\Models\AmiIndicator;
use App\Models\AmiPtkFinding;
use App\Models\AmiRecommendation;
use App\Models\AmiStandardSummary;
use App\Models\Category;
use App\Models\DataForm;
use App\Models\DataRequest;
use App\Models\Dataset;
use App\Models\FormSubmission;
use App\Models\NewsArticle;
use App\Models\User;
use App\Models\Visualization;
use App\Services\ChartPaletteService;
use Database\Seeders\SatuDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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

    public function test_public_pages_render_with_compiled_vite_assets(): void
    {
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

        $home = $this->get('/');
        $home->assertSee('/build/assets/', false);
        $home->assertSee('Satu Data Sistem Informasi', false);
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
        // Wrong password rejected
        $this->from('/login')->post(route('login.post'), [
            'nim' => 'admin',
            'password' => 'wrongpass',
        ])->assertRedirect('/login')
            ->assertSessionHas('error');
        $this->assertGuest();

        // Inactive account rejected on login
        $inactive = User::factory()->create([
            'code' => 'U-TEST-INACTIVE',
            'name' => 'Nonaktif User',
            'nim' => '9999999999',
            'role' => 'produsen',
            'jenis' => 'Mahasiswa',
            'password' => Hash::make('secret123'),
            'aktif' => false,
        ]);

        $this->from('/login')->post(route('login.post'), [
            'nim' => $inactive->nim,
            'password' => 'secret123',
        ])->assertRedirect('/login')
            ->assertSessionHas('error', 'Akun Anda dinonaktifkan. Hubungi Admin prodi.');
        $this->assertGuest();

        // Valid logins redirect to panel ringkasan
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

        // Idle timeout >= 30 minutes auto-logs user out and flashes notice
        $admin = User::query()->where('nim', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['last_activity_at' => now()->subMinutes(31)->timestamp])
            ->get(route('panel.ringkasan'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('notice');

        $this->assertGuest();

        // Mid-session deactivation also logs user out
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
        // Guest redirected to login
        $this->get(route('panel.ringkasan'))->assertRedirect(route('login'));

        $produsen = User::query()->where('nim', '1462200031')->firstOrFail();
        $wali = User::query()->where('nim', 'walidata')->firstOrFail();
        $admin = User::query()->where('nim', 'admin')->firstOrFail();

        // Produsen access
        $this->actingAs($produsen)->get(route('panel.ringkasan'))->assertStatus(200);
        $this->actingAs($produsen)->get(route('panel.isi'))->assertStatus(200);
        $this->actingAs($produsen)->get(route('panel.riwayat'))->assertStatus(200);

        foreach ([
            route('panel.formulir'),
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
        $this->actingAs($wali)->get(route('panel.data'))->assertStatus(200);
        $this->actingAs($wali)->get(route('panel.kategori'))->assertStatus(200);
        $this->actingAs($wali)->get(route('panel.dataset'))->assertStatus(200);
        $this->actingAs($wali)->get(route('panel.visualisasi'))->assertStatus(200);

        foreach (['menunggu', 'disetujui', 'ditolak', 'all'] as $tab) {
            $this->actingAs($wali)->get(route('panel.permohonan', ['tab' => $tab]))->assertStatus(200);
        }

        $this->actingAs($wali)->get(route('panel.isi'))->assertStatus(403);
        $this->actingAs($wali)->get(route('panel.pengguna'))->assertStatus(403);
        $this->actingAs($wali)->get(route('panel.konten'))->assertStatus(403);

        $sampleForm = DataForm::query()->firstOrFail();
        $sampleCat = Category::query()->firstOrFail();
        $sampleDataset = Dataset::query()->firstOrFail();

        $this->actingAs($wali)->delete(route('panel.formulir.destroy', $sampleForm->code))->assertStatus(403);
        $this->actingAs($wali)->delete(route('panel.kategori.destroy', $sampleCat->id))->assertStatus(403);
        $this->actingAs($wali)->delete(route('panel.dataset.destroy', $sampleDataset->code))->assertStatus(403);

        // Admin access across all permitted routes & kontentabs
        $this->actingAs($admin)->get(route('panel.ringkasan'))->assertStatus(200);
        $this->actingAs($admin)->get(route('panel.riwayat'))->assertStatus(200);
        $this->actingAs($admin)->get(route('panel.formulir'))->assertStatus(200);
        $this->actingAs($admin)->get(route('panel.data'))->assertStatus(200);
        $this->actingAs($admin)->get(route('panel.kategori'))->assertStatus(200);
        $this->actingAs($admin)->get(route('panel.dataset'))->assertStatus(200);
        $this->actingAs($admin)->get(route('panel.visualisasi'))->assertStatus(200);
        $this->actingAs($admin)->get(route('panel.permohonan'))->assertStatus(200);
        $this->actingAs($admin)->get(route('panel.pengguna'))->assertStatus(200);

        foreach (['profil', 'dosen', 'prestasi', 'berita', 'kurikulum'] as $tab) {
            $this->actingAs($admin)->get(route('panel.konten', ['tab' => $tab]))->assertStatus(200);
        }

        $this->actingAs($admin)->get(route('panel.isi'))->assertStatus(403);
    }

    public function test_produsen_multipart_form_submission_attachment_download_and_deadline_guard(): void
    {
        Storage::fake('local');

        $admin = User::query()->where('nim', 'admin')->firstOrFail();
        $wali = User::query()->where('nim', 'walidata')->firstOrFail();
        $mhs1 = User::query()->where('nim', '1462200031')->firstOrFail();
        $mhs2 = User::factory()->create([
            'code' => 'U-MHS-OTHER',
            'name' => 'Mahasiswa Lain',
            'nim' => '1462200099',
            'role' => 'produsen',
            'jenis' => 'Mahasiswa',
            'password' => Hash::make('mhs123'),
            'aktif' => true,
        ]);
        $category = Category::query()->firstOrFail();

        $openForm = DataForm::create([
            'code' => 'F-TEST-OPEN',
            'category_id' => $category->id,
            'title' => 'Formulir Uji Lampiran',
            'description' => 'Pengujian submit multipart',
            'tahun' => 2026,
            'periode' => 'Genap',
            'target' => 'Mahasiswa',
            'deadline' => now()->addDays(7),
            'fields' => [
                ['id' => 'judul', 'label' => 'Judul Kegiatan', 'type' => 'text', 'required' => true],
                ['id' => 'poin', 'label' => 'Poin', 'type' => 'number', 'required' => true],
                ['id' => 'bukti', 'label' => 'Bukti PDF', 'type' => 'file', 'required' => true],
            ],
            'created_by' => $admin->id,
        ]);

        $pdf = UploadedFile::fake()->create('sertifikat-lomba.pdf', 120, 'application/pdf');

        $res = $this->actingAs($mhs1)->post(route('panel.isi.submit', $openForm->code), [
            'values' => [
                'judul' => 'Juara Lomba Data',
                'poin' => '100',
            ],
            'files' => [
                'bukti' => $pdf,
            ],
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('success', 'Formulir Berhasil Disimpan');

        $submission = FormSubmission::query()
            ->where('data_form_id', $openForm->id)
            ->where('user_id', $mhs1->id)
            ->firstOrFail();

        $storedPath = $submission->attachments['bukti'] ?? '';
        $this->assertNotSame('', $storedPath);
        Storage::disk('local')->assertExists($storedPath);

        // Owner & Wali Data can download attachment, other produsen gets 403
        $this->actingAs($mhs1)
            ->get(route('panel.submissions.file', [$submission->code, 'bukti']))
            ->assertStatus(200);

        $this->actingAs($wali)
            ->get(route('panel.submissions.file', [$submission->code, 'bukti']))
            ->assertStatus(200);

        $this->actingAs($mhs2)
            ->get(route('panel.submissions.file', [$submission->code, 'bukti']))
            ->assertStatus(403);

        // Closed form rejects submissions
        $closedForm = DataForm::create([
            'code' => 'F-TEST-CLOSED',
            'category_id' => $category->id,
            'title' => 'Formulir Tutup',
            'description' => 'Sudah lewat tenggat',
            'tahun' => 2026,
            'periode' => 'Genap',
            'target' => 'Mahasiswa',
            'deadline' => now()->subDay(),
            'fields' => [
                ['id' => 'judul', 'label' => 'Judul', 'type' => 'text', 'required' => true],
            ],
            'created_by' => $admin->id,
        ]);

        $this->actingAs($mhs1)
            ->postJson(route('panel.isi.submit', $closedForm->code), [
                'values' => ['judul' => 'Telat'],
            ])
            ->assertStatus(422);

        $this->assertSame(0, $closedForm->submissions()->count());
    }

    public function test_data_quality_cleaning_and_dataset_builder_modes(): void
    {
        $wali = User::query()->where('nim', 'walidata')->firstOrFail();
        $mhs = User::query()->where('nim', '1462200031')->firstOrFail();
        $dosen = User::query()->where('nim', '0712048601')->firstOrFail();
        $category = Category::query()->firstOrFail();

        $form = DataForm::create([
            'code' => 'F-QUALITY-TEST',
            'category_id' => $category->id,
            'title' => 'Survey Praktikum SI',
            'description' => 'Uji kualitas & dataset builder',
            'tahun' => 2026,
            'periode' => 'Ganjil',
            'target' => 'Semua',
            'deadline' => now()->addDays(10),
            'fields' => [
                ['id' => 'lab', 'label' => 'Laboratorium', 'type' => 'select', 'required' => true, 'options' => ['Lab Basis Data', 'Lab Jaringan']],
                ['id' => 'nilai', 'label' => 'Nilai Akhir', 'type' => 'number', 'required' => true],
            ],
            'created_by' => $wali->id,
        ]);

        // Dirty submission (non-numeric nilai + invalid whitespace on required field)
        $dirtySub = FormSubmission::create([
            'code' => 'S-DIRTY-01',
            'data_form_id' => $form->id,
            'user_id' => $mhs->id,
            'values' => ['lab' => 'Lab Basis Data', 'nilai' => 'sembilan puluh'],
            'submitted_at' => now()->subHour(),
        ]);

        $cleanSub = FormSubmission::create([
            'code' => 'S-CLEAN-01',
            'data_form_id' => $form->id,
            'user_id' => $dosen->id,
            'values' => ['lab' => 'Lab Jaringan', 'nilai' => '95'],
            'submitted_at' => now(),
        ]);

        // Dirty guard blocks dataset creation
        $this->actingAs($wali)->from(route('panel.dataset'))
            ->post(route('panel.dataset.from-form'), [
                'form_id' => $form->id,
                'mode' => 'rekap',
                'group_field_id' => 'lab',
                'title' => 'Rekap Lab Gagal',
                'category_id' => $category->id,
                'tahun' => 2026,
                'periode' => 'Ganjil',
                'access' => 'public',
                'format' => 'XLSX',
            ])
            ->assertRedirect(route('panel.dataset'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('datasets', ['title' => 'Rekap Lab Gagal']);

        // Clean dirty submission
        $this->actingAs($wali)->from(route('panel.data', ['form' => $form->code]))
            ->put(route('panel.data.submissions.update', $dirtySub->code), [
                'values' => ['lab' => 'Lab Basis Data', 'nilai' => '90'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $dirtySub->refresh();
        $this->assertSame($wali->id, $dirtySub->cleaned_by);
        $this->assertNotNull($dirtySub->cleaned_at);

        // Collected raw CSV export
        $csvRes = $this->actingAs($wali)->get(route('panel.data.csv', $form->code));
        $csvRes->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $csvRes->headers->get('content-type'));

        // Build rekap dataset
        $this->actingAs($wali)->post(route('panel.dataset.from-form'), [
            'form_id' => $form->id,
            'mode' => 'rekap',
            'group_field_id' => 'lab',
            'title' => 'Rekap Praktikum SI 2026',
            'category_id' => $category->id,
            'tahun' => 2026,
            'periode' => 'Ganjil',
            'access' => 'public',
            'format' => 'XLSX',
        ])->assertRedirect(route('panel.dataset'))
            ->assertSessionHas('success');

        $rekapDataset = Dataset::query()->where('title', 'Rekap Praktikum SI 2026')->firstOrFail();
        $this->assertSame(['Laboratorium', 'Jumlah'], $rekapDataset->cols);
        $this->assertCount(2, $rekapDataset->rows);

        // Build rinci dataset
        $this->actingAs($wali)->post(route('panel.dataset.from-form'), [
            'form_id' => $form->id,
            'mode' => 'rinci',
            'picked_cols' => ['_nama', 'lab', 'nilai'],
            'title' => 'Rinci Praktikum SI 2026',
            'category_id' => $category->id,
            'tahun' => 2026,
            'periode' => 'Ganjil',
            'access' => 'private',
            'format' => 'CSV',
        ])->assertRedirect(route('panel.dataset'))
            ->assertSessionHas('success');

        $rinciDataset = Dataset::query()->where('title', 'Rinci Praktikum SI 2026')->firstOrFail();
        $this->assertSame(['Nama pengirim', 'Laboratorium', 'Nilai Akhir'], $rinciDataset->cols);
        $this->assertCount(2, $rinciDataset->rows);
        $this->assertSame('private', $rinciDataset->access);
    }

    public function test_private_dataset_request_approval_rejection_and_csv_xlsx_downloads(): void
    {
        $wali = User::query()->where('nim', 'walidata')->firstOrFail();
        $publicDs = Dataset::query()->where('access', 'public')->firstOrFail();
        $privateDs = Dataset::query()->where('access', 'private')->firstOrFail();

        // Public dataset is directly downloadable as CSV & valid OOXML XLSX
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

        $zip = new ZipArchive();
        $opened = $zip->open($xlsxPath);
        $this->assertTrue($opened === true, 'XLSX harus valid ZipArchive.');
        $this->assertNotFalse($zip->locateName('xl/workbook.xml'));
        $this->assertNotFalse($zip->locateName('xl/worksheets/sheet1.xml'));
        $zip->close();
        @unlink($xlsxPath);

        // Guest blocked from private dataset before approval
        $this->get(route('dataset.preview', $privateDs->code))->assertStatus(403);
        $this->get(route('dataset.download', [$privateDs->code, 'csv']))->assertStatus(403);
        $this->get(route('dataset.download', [$privateDs->code, 'xlsx']))->assertStatus(403);

        // Request 1 -> rejected (first verify rejecting without note fails validation)
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

        // Reject without note -> validation error
        $this->actingAs($wali)->from(route('panel.permohonan'))
            ->post(route('panel.permohonan.decide', $req1Code), [
                'status' => 'ditolak',
                'note' => '',
            ])
            ->assertRedirect(route('panel.permohonan'))
            ->assertSessionHasErrors('note');

        // Reject with note -> rejected, guest session still blocked (403)
        $this->actingAs($wali)->post(route('panel.permohonan.decide', $req1Code), [
            'status' => 'ditolak',
            'note' => 'Dokumen surat izin belum dilampirkan.',
        ])->assertRedirect(route('panel.permohonan', ['tab' => 'menunggu']));

        // Reset auth back to guest and verify rejected request stays blocked (403)
        $this->post(route('logout'));

        $this->withSession(['my_request_codes' => [$req1Code]])
            ->get(route('dataset.preview', $privateDs->code))
            ->assertStatus(403);

        // Submit Request 2 as guest -> approved

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

        // Guest with approved session request code now has preview & downloads
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
            'name' => 'Wali Data Kedua',
            'nim' => 'walidata2',
            'role' => 'wali',
            'password' => Hash::make('wali123'),
            'aktif' => true,
        ]);

        $publicDs = Dataset::query()->where('access', 'public')->get()->first(
            fn (Dataset $d): bool => ! empty($d->numericColumns())
        );
        $this->assertNotNull($publicDs);
        $numericCols = $publicDs->numericColumns();
        $valueCol = (int) array_key_first($numericCols);

        $title = 'Visualisasi Uji Dinamis 2026';

        $this->actingAs($wali1)->post(route('panel.visualisasi.store'), [
            'title' => $title,
            'dataset_id' => $publicDs->id,
            'label_col' => 0,
            'value_col' => $valueCol,
            'kind' => 'bar',
            'color' => 'sky',
            'show' => '1',
        ])->assertRedirect(route('panel.visualisasi'))
            ->assertSessionHas('success');

        $viz = Visualization::query()->where('title', $title)->firstOrFail();
        $this->assertTrue($viz->show);

        // Appears on public Dashboard
        $this->get('/dashboard')->assertStatus(200)->assertSee($title, false);

        // Toggle visibility off
        $this->actingAs($wali1)->patchJson(route('panel.visualisasi.toggle', $viz->code), ['show' => false])
            ->assertStatus(200)
            ->assertJsonPath('show', false);

        $this->get('/dashboard')->assertStatus(200)->assertDontSee($title, false);

        // Non-creator wali cannot delete another wali's visualization
        $this->actingAs($wali2)->delete(route('panel.visualisasi.destroy', $viz->code))
            ->assertStatus(403);

        // Creator wali can delete own visualization
        $this->actingAs($wali1)->delete(route('panel.visualisasi.destroy', $viz->code))
            ->assertRedirect(route('panel.visualisasi'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('visualizations', ['id' => $viz->id]);
    }

    public function test_admin_crud_and_self_or_dependency_guards(): void
    {
        $admin = User::query()->where('nim', 'admin')->firstOrFail();

        // Self delete / self demote blocked
        $this->actingAs($admin)->from(route('panel.pengguna'))
            ->delete(route('panel.pengguna.destroy', $admin->id))
            ->assertRedirect(route('panel.pengguna'))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

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

        // Create, update, delete user
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
        $this->assertDatabaseMissing('users', ['id' => $createdUser->id]);

        // Category in use cannot be deleted; unused category can be created and deleted
        $usedCat = Category::query()->whereHas('datasets')->firstOrFail();
        $this->actingAs($admin)->from(route('panel.kategori'))
            ->delete(route('panel.kategori.destroy', $usedCat->id))
            ->assertRedirect(route('panel.kategori'))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $usedCat->id]);

        $this->actingAs($admin)->post(route('panel.kategori.store'), [
            'code' => 'KT-TEST-99',
            'name' => 'Kategori Sementara',
            'description' => 'Bisa dihapus',
        ])->assertRedirect(route('panel.kategori'));

        $unusedCat = Category::query()->where('code', 'KT-TEST-99')->firstOrFail();
        $this->actingAs($admin)->delete(route('panel.kategori.destroy', $unusedCat->id))
            ->assertRedirect(route('panel.kategori'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', ['id' => $unusedCat->id]);

        // Admin CMS content updates reflect on /, /berita, /tentang-kami
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

    public function test_ami_workbook_data_and_datasets_are_seeded_and_downloadable(): void
    {
        // Verify decimal & thousand-separator numeric parsing
        $this->assertSame(83.33, ChartPaletteService::parseNumeric('83.33'));
        $this->assertSame(1250.5, ChartPaletteService::parseNumeric('1.250,50'));
        $this->assertSame(1250000.0, ChartPaletteService::parseNumeric('1.250.000'));

        // All 5 normalized AMI tables populated completely
        $this->assertSame(240, AmiIndicator::query()->count());
        $this->assertSame(37, AmiStandardSummary::query()->count());
        $this->assertSame(361, AmiBenchmark::query()->count());
        $this->assertSame(113, AmiPtkFinding::query()->count());
        $this->assertSame(4, AmiRecommendation::query()->count());

        // Category KT-AMI-01, Form F-05, and Visualizations V-04 & V-05
        $this->assertDatabaseHas('categories', ['code' => 'KT-AMI-01']);

        $amiForm = DataForm::query()->where('code', 'F-05')->firstOrFail();
        $this->assertSame('Dosen', $amiForm->target);

        $wali = User::query()->where('nim', 'walidata')->firstOrFail();
        $admin = User::query()->where('nim', 'admin')->firstOrFail();
        $this->actingAs($wali)->get(route('panel.formulir'))
            ->assertStatus(200)
            ->assertSee($amiForm->title);
        $this->actingAs($admin)->get(route('panel.formulir'))
            ->assertStatus(200)
            ->assertSee($amiForm->title);

        $vis04 = Visualization::query()->where('code', 'V-04')->firstOrFail();
        $vis05 = Visualization::query()->where('code', 'V-05')->firstOrFail();
        $this->assertTrue((bool) $vis04->show);
        $this->assertTrue((bool) $vis05->show);

        $this->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee($vis04->title, false)
            ->assertSee($vis05->title, false);

        // Datasets DS-13..DS-22 are public, previewable, and downloadable as CSV
        for ($i = 13; $i <= 22; $i++) {
            $code = 'DS-'.$i;
            $dataset = Dataset::query()->where('code', $code)->firstOrFail();

            $this->assertSame('public', $dataset->access);
            $this->assertNotEmpty($dataset->rows, "Dataset {$code} harus memiliki baris data.");

            $this->get(route('dataset.preview', $dataset->code))
                ->assertStatus(200)
                ->assertJsonPath('id', $dataset->code);

            $csvResponse = $this->get(route('dataset.download', [$dataset->code, 'csv']));
            $csvResponse->assertStatus(200);
            $this->assertStringContainsString('text/csv', (string) $csvResponse->headers->get('content-type'));
        }

        // Verify valid OOXML XLSX export for representative multi-column AMI datasets
        foreach (['DS-18', 'DS-20'] as $xlsxCode) {
            $xlsxResponse = $this->get(route('dataset.download', [$xlsxCode, 'xlsx']));
            $xlsxResponse->assertStatus(200);
            $xlsxBytes = $xlsxResponse->getContent();
            $this->assertNotEmpty($xlsxBytes);
            $xlsxPath = tempnam(sys_get_temp_dir(), 'test_xlsx_');
            $this->assertNotFalse($xlsxPath);
            file_put_contents($xlsxPath, $xlsxBytes);

            $zip = new ZipArchive();
            $opened = $zip->open($xlsxPath);
            $this->assertTrue($opened === true, "XLSX {$xlsxCode} harus berupa ZipArchive yang valid.");
            $this->assertNotFalse($zip->locateName('xl/workbook.xml'));
            $this->assertNotFalse($zip->locateName('xl/worksheets/sheet1.xml'));
            $zip->close();
            @unlink($xlsxPath);
        }
    }
}
