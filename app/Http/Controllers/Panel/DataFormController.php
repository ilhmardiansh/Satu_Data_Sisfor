<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DataForm;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Services\ChartPaletteService;
use App\Services\DataQualityService;
use App\Support\CodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataFormController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'all');
        if (! in_array($status, ['all', 'open', 'closed'], true)) {
            $status = 'all';
        }

        $counts = [
            'all' => DataForm::query()->count(),
            'open' => DataForm::open()->count(),
            'closed' => DataForm::closed()->count(),
        ];

        $query = DataForm::query()
            ->with(['category', 'kolomFormulir'])
            ->withCount([
                'submissions',
                'submissions as valid_submissions_count' => fn ($q) => $q->where('status_validasi', 'valid'),
                'submissions as raw_submissions_count' => fn ($q) => $q->where('status_validasi', 'mentah'),
            ])
            ->orderByDesc('created_at');

        if ($status === 'open') {
            $query->open();
        } elseif ($status === 'closed') {
            $query->closed();
        }

        $forms = $query->paginate(10)->withQueryString();
        $categories = Category::query()->orderBy('id')->get();
        $templates = $this->formTemplates();

        return view('panel.formulir', compact('forms', 'categories', 'status', 'counts', 'templates'));
    }

    public function create(Request $request): View
    {
        $categories = Category::query()->orderBy('id')->get();
        $templateSlug = trim((string) $request->query('template', 'kosong'));
        $templates = collect($this->formTemplates())->keyBy('slug');
        $tpl = $templates->get($templateSlug) ?? $templates->get('kosong');

        $defaultDeadline = now()->addDays(14)->format('Y-m-d\TH:i');
        $defaultCategoryId = (string) ($categories->first()?->id ?? '');
        $defaults = DataForm::defaultSettings();
        $tplSettings = is_array($tpl['settings'] ?? null) ? $tpl['settings'] : [];
        $settings = array_merge($defaults, $tplSettings);
        $settings['theme'] = array_merge(
            $defaults['theme'],
            is_array($tplSettings['theme'] ?? null) ? $tplSettings['theme'] : []
        );

        $studioPayload = [
            'mode' => 'create',
            'action' => route('panel.formulir.store'),
            'listUrl' => route('panel.formulir'),
            'uploadUrl' => route('panel.formulir.media.upload'),
            'formCode' => null,
            'publicUrl' => null,
            'preprocessUrl' => null,
            'csvUrl' => null,
            'duplicateUrl' => null,
            'meta' => [
                'title' => (string) ($tpl['default_title'] ?? ''),
                'description' => (string) ($tpl['default_description'] ?? ''),
                'category_id' => $defaultCategoryId,
                'tahun' => (string) date('Y'),
                'periode' => (int) date('n') >= 8 ? 'Ganjil' : 'Genap',
                'target' => (string) ($tpl['default_target'] ?? 'Mahasiswa'),
                'deadline' => $defaultDeadline,
                'batasi_satu_respon' => false,
                'status_aktif' => true,
            ],
            'settings' => $settings,
            'items' => $tpl['items'] ?? [
                [
                    'id' => 'q1',
                    'label' => 'Pertanyaan Tanpa Judul',
                    'help' => '',
                    'type' => 'radio',
                    'required' => false,
                    'options' => ['Opsi 1', 'Opsi 2'],
                    'config' => [],
                ],
            ],
            'importableForms' => $this->importableFormsPayload(),
            'responses' => [
                'total' => 0,
                'valid' => 0,
                'mentah' => 0,
                'ditolak' => 0,
                'accepting' => true,
                'questions' => [],
                'submissions' => [],
            ],
        ];

        $dataForm = null;

        return view('panel.formulir-editor', compact('categories', 'studioPayload', 'dataForm'));
    }

    public function edit(DataForm $dataForm, DataQualityService $quality): View
    {
        $dataForm->loadMissing(['category', 'kolomFormulir', 'submissions.user', 'submissions.dataIsian.kolomFormulir']);
        $categories = Category::query()->orderBy('id')->get();
        $defaultDeadline = now()->addDays(14)->format('Y-m-d\TH:i');

        $studioPayload = [
            'mode' => 'edit',
            'action' => route('panel.formulir.update', $dataForm->code),
            'listUrl' => route('panel.formulir'),
            'uploadUrl' => route('panel.formulir.media.upload'),
            'formCode' => $dataForm->code,
            'publicUrl' => route('formulir.show', $dataForm->code),
            'preprocessUrl' => route('panel.preprocessing.show', $dataForm->code),
            'csvUrl' => route('panel.preprocessing.csv', $dataForm->code),
            'duplicateUrl' => route('panel.formulir.duplicate', $dataForm->code),
            'meta' => [
                'title' => $dataForm->title,
                'description' => (string) ($dataForm->description ?? ''),
                'category_id' => (string) $dataForm->category_id,
                'tahun' => (string) $dataForm->tahun,
                'periode' => $dataForm->periode,
                'target' => $dataForm->target,
                'deadline' => $dataForm->deadline?->format('Y-m-d\TH:i') ?? $defaultDeadline,
                'batasi_satu_respon' => (bool) $dataForm->batasi_satu_respon,
                'status_aktif' => (bool) $dataForm->status_aktif,
            ],
            'settings' => $dataForm->settings,
            'items' => $dataForm->items ?? [],
            'importableForms' => $this->importableFormsPayload($dataForm->id),
            'responses' => $this->buildResponsesPayload($dataForm, $quality),
        ];

        return view('panel.formulir-editor', compact('categories', 'studioPayload', 'dataForm'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request, true);

        $form = DB::transaction(function () use ($request, $data): DataForm {
            $created = DataForm::create([
                'code' => CodeGenerator::next('F', 'formulir'),
                'id_kategori' => $data['category_id'],
                'judul' => $data['title'],
                'deskripsi' => $data['description'],
                'tahun' => $data['tahun'],
                'periode' => $data['periode'],
                'target' => $data['target'],
                'batasi_satu_respon' => $data['batasi_satu_respon'],
                'pengaturan' => $data['settings'],
                'status_preprocessing' => 'belum',
                'tanggal_waktu' => $data['deadline'],
                'status_aktif' => true,
                'dibuat_oleh' => $request->user()->id,
            ]);

            $created->syncFields($data['fields']);

            return $created;
        });

        if ($request->input('after_save') === 'edit') {
            return redirect()->route('panel.formulir.edit', $form->code)
                ->with('success', 'Formulir Berhasil Dibuat');
        }

        return redirect()->route('panel.formulir')
            ->with('success', 'Formulir Berhasil Dibuat');
    }

    public function update(Request $request, DataForm $dataForm): RedirectResponse
    {
        $data = $this->validatePayload($request, false);
        $isActive = $request->has('status_aktif')
            ? ($request->boolean('status_aktif') && $data['deadline']->isFuture())
            : $data['deadline']->isFuture();

        DB::transaction(function () use ($dataForm, $data, $isActive): void {
            $dataForm->update([
                'id_kategori' => $data['category_id'],
                'judul' => $data['title'],
                'deskripsi' => $data['description'],
                'tahun' => $data['tahun'],
                'periode' => $data['periode'],
                'target' => $data['target'],
                'batasi_satu_respon' => $data['batasi_satu_respon'],
                'pengaturan' => $data['settings'],
                'tanggal_waktu' => $data['deadline'],
                'status_aktif' => $isActive,
            ]);

            $dataForm->syncFields($data['fields']);
        });

        if ($request->input('after_save') === 'edit') {
            return redirect()->route('panel.formulir.edit', $dataForm->code)
                ->with('success', 'Perubahan formulir disimpan.');
        }

        return redirect()->route('panel.formulir')
            ->with('success', 'Formulir diperbarui.');
    }

    public function duplicate(Request $request, DataForm $dataForm): RedirectResponse
    {
        $dataForm->loadMissing('kolomFormulir');

        $clone = DB::transaction(function () use ($request, $dataForm): DataForm {
            $deadline = $dataForm->tanggal_waktu && $dataForm->tanggal_waktu->isFuture()
                ? $dataForm->tanggal_waktu->copy()
                : now()->addDays(14);

            $copy = DataForm::create([
                'code' => CodeGenerator::next('F', 'formulir'),
                'id_kategori' => $dataForm->id_kategori,
                'judul' => $dataForm->judul.' (Salinan)',
                'deskripsi' => $dataForm->deskripsi,
                'tahun' => $dataForm->tahun,
                'periode' => $dataForm->periode,
                'target' => $dataForm->target,
                'batasi_satu_respon' => (bool) $dataForm->batasi_satu_respon,
                'pengaturan' => $dataForm->pengaturan,
                'status_preprocessing' => 'belum',
                'tanggal_waktu' => $deadline,
                'status_aktif' => true,
                'dibuat_oleh' => $request->user()->id,
            ]);

            $copy->syncFields($dataForm->items ?? []);

            return $copy;
        });

        return redirect()->route('panel.formulir.edit', $clone->code)
            ->with('success', "Formulir berhasil diduplikasi sebagai {$clone->code}.");
    }

    /**
     * Extract non-empty parameter across form input, raw JSON payload, query string, or HTTP header.
     *
     * @param  array<string, mixed>  $json
     */
    private function extractUploadParam(Request $request, array $json, string $key, ?string $header = null): string
    {
        $candidates = [
            $request->input($key),
            $json[$key] ?? null,
            $request->query($key),
            $header !== null ? $request->header($header) : null,
        ];

        foreach ($candidates as $val) {
            if ($val === null || is_array($val) || is_object($val)) {
                continue;
            }
            $str = trim((string) $val);
            if ($str !== '') {
                return $str;
            }
        }

        return '';
    }

    public function uploadMedia(Request $request): JsonResponse
    {
        $allowedMediaExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'avif', 'jfif'];

        $rawContent = (string) $request->getContent();
        $jsonDecoded = json_decode($rawContent, true);
        $jsonPayload = is_array($jsonDecoded) ? $jsonDecoded : [];

        // Support JSON Base64, FormData, & raw binary chunked uploads for large banners/images on PHP servers with small upload_max_filesize
        $uploadIdParam = $this->extractUploadParam($request, $jsonPayload, 'upload_id', 'X-Upload-Id');
        $chunkIndexParam = $this->extractUploadParam($request, $jsonPayload, 'chunk_index', 'X-Chunk-Index');
        $isChunked = $uploadIdParam !== '' && $chunkIndexParam !== '';

        if ($isChunked) {
            $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', $uploadIdParam) ?: 'media';
            $chunkIndex = max(0, (int) $chunkIndexParam);
            $totalChunksRaw = $this->extractUploadParam($request, $jsonPayload, 'total_chunks', 'X-Total-Chunks');
            $totalChunks = $totalChunksRaw !== '' ? max(1, min(131072, (int) $totalChunksRaw)) : 1;
            $rawName = $this->extractUploadParam($request, $jsonPayload, 'file_name', null);
            $origName = basename($rawName !== ''
                ? $rawName
                : rawurldecode((string) $request->header('X-File-Name', 'image.png')));
            $ext = strtolower((string) pathinfo($origName, PATHINFO_EXTENSION));

            if (! in_array($ext, $allowedMediaExts, true)) {
                return response()->json(['message' => 'Format gambar harus JPG, JPEG, PNG, WEBP, GIF, BMP, atau AVIF.'], 422);
            }

            $chunkB64 = $this->extractUploadParam($request, $jsonPayload, 'chunk_b64', null);
            if ($chunkB64 !== '') {
                $decoded = base64_decode($chunkB64, true);
                if ($decoded === false) {
                    return response()->json(['message' => 'Format potongan gambar tidak valid.'], 422);
                }
                $bytes = $decoded;
            } elseif (($chunkFile = $request->file('chunk')) !== null) {
                if (! $chunkFile->isValid()) {
                    return response()->json(['message' => 'Potongan gambar gagal diterima server (kode PHP '.$chunkFile->getError().').'], 422);
                }
                $bytes = file_get_contents($chunkFile->getRealPath());
                $bytes = $bytes === false ? '' : $bytes;
            } else {
                $bytes = $rawContent;
            }

            $userId = (int) ($request->user()?->id ?? 0);
            $chunkRel = "chunks/media_{$userId}_{$uploadId}.part";
            $chunkAbs = Storage::disk('local')->path($chunkRel);
            $chunkDir = dirname($chunkAbs);
            if (! is_dir($chunkDir)) {
                mkdir($chunkDir, 0775, true);
            }

            if ($bytes === '') {
                if ($chunkIndex === 0 && is_file($chunkAbs)) {
                    @unlink($chunkAbs);
                }

                return response()->json(['message' => 'Potongan gambar kosong (0 byte) atau gagal diterima server.'], 422);
            }

            $mode = $chunkIndex === 0 ? 'wb' : 'ab';
            $fp = fopen($chunkAbs, $mode);
            if ($fp === false) {
                return response()->json(['message' => 'Gagal menulis potongan berkas gambar.'], 500);
            }
            fwrite($fp, $bytes);
            fclose($fp);

            clearstatcache(true, $chunkAbs);
            $currentSize = (int) filesize($chunkAbs);
            if ($currentSize > 50 * 1024 * 1024) {
                @unlink($chunkAbs);

                return response()->json(['message' => 'Ukuran gambar maksimal 50 MB.'], 422);
            }

            if ($chunkIndex < $totalChunks - 1) {
                return response()->json(['ok' => true, 'completed' => false, 'chunk' => $chunkIndex]);
            }

            if ($currentSize <= 0) {
                @unlink($chunkAbs);

                return response()->json(['message' => 'Gambar kosong (0 byte) tidak dapat disimpan.'], 422);
            }

            $targetRel = 'form-media/'.date('Y/m').'/'.uniqid('img_', true).'.'.$ext;
            $targetAbs = Storage::disk('local')->path($targetRel);
            $targetDir = dirname($targetAbs);
            if (! is_dir($targetDir)) {
                mkdir($targetDir, 0775, true);
            }
            rename($chunkAbs, $targetAbs);

            if (! Storage::disk('local')->exists($targetRel) || (int) Storage::disk('local')->size($targetRel) <= 0) {
                return response()->json(['message' => 'Gambar gagal disimpan ke penyimpanan server.'], 500);
            }

            return response()->json([
                'ok' => true,
                'completed' => true,
                'url' => route('formulir.media', ['path' => $targetRel], false),
                'path' => $targetRel,
            ], 201);
        }

        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif,bmp,avif,jfif', 'max:51200'],
        ], [
            'image.required' => 'Pilih berkas gambar terlebih dahulu.',
            'image.image' => 'Berkas harus berupa gambar yang valid.',
            'image.mimes' => 'Format gambar harus JPG, JPEG, PNG, WEBP, GIF, BMP, atau AVIF.',
            'image.max' => 'Ukuran gambar maksimal 50 MB.',
        ]);

        $file = $request->file('image');
        $storedPath = $file->store('form-media/'.date('Y/m'), 'local');

        if ($storedPath === false) {
            return response()->json(['message' => 'Gagal menyimpan berkas gambar.'], 500);
        }

        return response()->json([
            'url' => route('formulir.media', ['path' => $storedPath], false),
            'path' => $storedPath,
        ], 201);
    }

    public function serveMedia(string $path): StreamedResponse
    {
        $clean = str_replace('\\', '/', ltrim($path, '/'));
        if (
            $clean === ''
            || str_contains($clean, '..')
            || str_contains($clean, "\0")
            || ! str_starts_with($clean, 'form-media/')
            || ! Storage::disk('local')->exists($clean)
        ) {
            abort(404);
        }

        return Storage::disk('local')->response($clean, null, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function toggleActive(DataForm $dataForm): RedirectResponse
    {
        if ($dataForm->status_aktif) {
            $dataForm->tutupFormulir();

            return back()->with('info', "Formulir “{$dataForm->judul}” ditutup.");
        }

        if ($dataForm->tanggal_waktu === null || $dataForm->tanggal_waktu->isPast()) {
            return back()->with('error', 'Tenggat waktu formulir sudah lewat. Perpanjang tenggat melalui halaman Ubah sebelum membuka kembali.');
        }

        $dataForm->update(['status_aktif' => true]);

        return back()->with('success', "Formulir “{$dataForm->judul}” dibuka kembali.");
    }

    public function destroy(DataForm $dataForm): RedirectResponse
    {
        $dataForm->delete();

        return redirect()->route('panel.formulir')
            ->with('success', 'Formulir dihapus.');
    }

    /**
     * Curated UNUSA Google Forms quick-start templates for the gallery.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function formTemplates(): array
    {
        return [
            [
                'slug' => 'kosong',
                'name' => 'Formulir Kosong',
                'badge' => 'Standar',
                'desc' => 'Mulai dari kanvas bersih dengan seluruh 17 jenis pertanyaan dan blok aset Google Forms.',
                'icon' => 'file-plus-2',
                'default_title' => '',
                'default_description' => '',
                'default_target' => 'Mahasiswa',
                'settings' => [],
                'items' => [
                    [
                        'id' => 'q1',
                        'label' => 'Pertanyaan Tanpa Judul',
                        'help' => '',
                        'type' => 'radio',
                        'required' => false,
                        'options' => ['Opsi 1', 'Opsi 2'],
                        'config' => [],
                    ],
                ],
            ],
            [
                'slug' => 'survei-likert',
                'name' => 'Survei Kepuasan & Likert',
                'badge' => 'Skala & Kisi',
                'desc' => 'Template siap pakai dengan Skala Linier, Rating Bintang, Kisi Pilihan Ganda, dan Saran.',
                'icon' => 'sliders-horizontal',
                'default_title' => 'Survei Kepuasan Layanan Prodi Sistem Informasi',
                'default_description' => 'Masukan Anda menjadi dasar peningkatan mutu layanan akademik dan fasilitas laboratorium prodi.',
                'default_target' => 'Mahasiswa',
                'settings' => [
                    'theme' => ['palette' => 'benhur', 'header_preset' => 'benhur-arch'],
                    'show_progress_bar' => true,
                ],
                'items' => [
                    [
                        'id' => 'bagian_1',
                        'label' => 'Bagian 1: Evaluasi Layanan Utama',
                        'help' => 'Beri penilaian objektif atas layanan yang Anda terima pada semester berjalan.',
                        'type' => 'section',
                        'required' => false,
                        'config' => ['after_action' => 'next'],
                    ],
                    [
                        'id' => 'skala_puas',
                        'label' => 'Tingkat kepuasan terhadap layanan administrasi akademik',
                        'help' => '1 = Sangat Tidak Puas, 5 = Sangat Puas',
                        'type' => 'linear_scale',
                        'required' => true,
                        'config' => [
                            'scale_min' => 1,
                            'scale_max' => 5,
                            'scale_min_label' => 'Sangat Tidak Puas',
                            'scale_max_label' => 'Sangat Puas',
                        ],
                    ],
                    [
                        'id' => 'rating_lab',
                        'label' => 'Peringkat kenyamanan ruang kelas & laboratorium komputer',
                        'help' => 'Klik jumlah bintang sesuai pengalaman Anda',
                        'type' => 'rating',
                        'required' => true,
                        'config' => ['rating_max' => 5, 'rating_icon' => 'star'],
                    ],
                    [
                        'id' => 'kisi_aspek',
                        'label' => 'Evaluasi Aspek Pembelajaran',
                        'help' => 'Pilih satu penilaian pada setiap baris aspek berikut.',
                        'type' => 'mc_grid',
                        'required' => true,
                        'config' => [
                            'grid_rows' => ['Kejelasan RPS & Materi', 'Ketersediaan Modul Praktikum', 'Kecepatan Umpan Balik'],
                            'grid_cols' => ['Kurang', 'Cukup', 'Baik', 'Sangat Baik'],
                            'require_row_response' => true,
                            'limit_one_per_col' => false,
                        ],
                    ],
                    [
                        'id' => 'saran',
                        'label' => 'Saran dan masukan spesifik untuk perbaikan semester depan',
                        'help' => '',
                        'type' => 'textarea',
                        'required' => false,
                        'config' => [],
                    ],
                ],
            ],
            [
                'slug' => 'pendataan-prestasi',
                'name' => 'Pendataan Prestasi & Bukti',
                'badge' => 'Akreditasi',
                'desc' => 'Instrumen pencatatan prestasi dengan validasi angka, tanggal kegiatan, dan unggah sertifikat.',
                'icon' => 'award',
                'default_title' => 'Pendataan Rekognisi & Prestasi Sivitas SI',
                'default_description' => 'Laporkan prestasi lomba, sertifikasi profesi, atau publikasi beserta dokumen bukti resmi.',
                'default_target' => 'Semua',
                'settings' => [
                    'theme' => ['palette' => 'cerulean', 'header_preset' => 'cerulean-grid'],
                ],
                'items' => [
                    [
                        'id' => 'judul_kegiatan',
                        'label' => 'Nama Kompetisi / Sertifikasi / Publikasi',
                        'help' => 'Tulis lengkap sesuai sertifikat resmi',
                        'type' => 'text',
                        'required' => true,
                        'config' => [],
                    ],
                    [
                        'id' => 'tingkat',
                        'label' => 'Tingkat Capaian',
                        'help' => '',
                        'type' => 'radio',
                        'required' => true,
                        'options' => ['Kota / Provinsi', 'Nasional', 'Internasional'],
                        'config' => ['allow_other' => false],
                    ],
                    [
                        'id' => 'tgl_kegiatan',
                        'label' => 'Tanggal Pelaksanaan / Penerbitan Sertifikat',
                        'help' => '',
                        'type' => 'date',
                        'required' => true,
                        'config' => ['include_time' => false, 'include_year' => true],
                    ],
                    [
                        'id' => 'poin',
                        'label' => 'Jumlah Peserta / Tim',
                        'help' => 'Isi angka bulat minimal 1',
                        'type' => 'number',
                        'required' => true,
                        'config' => [
                            'validation' => [
                                'enabled' => true,
                                'category' => 'number',
                                'operator' => 'gte',
                                'value' => '1',
                                'message' => 'Jumlah minimal 1 orang.',
                            ],
                        ],
                    ],
                    [
                        'id' => 'bukti',
                        'label' => 'Unggah Dokumen / Sertifikat Bukti Pendukung',
                        'help' => 'Mendukung semua jenis berkas (PDF, Office, Gambar, ZIP, Video) · ukuran bebas',
                        'type' => 'file',
                        'required' => true,
                        'config' => [
                            'restrict_file_types' => false,
                            'file_types' => [],
                            'file_max_mb' => 0,
                            'file_max_count' => 1,
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'multi-bagian',
                'name' => 'Kuesioner Multi-Bagian Bercabang',
                'badge' => 'Percabangan',
                'desc' => 'Demonstrasi halaman bertingkat dengan lompatan bagian otomatis berdasarkan pilihan jawaban.',
                'icon' => 'git-branch',
                'default_title' => 'Pendataan Status Magang & Riset Mahasiswa',
                'default_description' => 'Formulir menyesuaikan pertanyaan lanjutan sesuai jalur kegiatan yang Anda pilih.',
                'default_target' => 'Mahasiswa',
                'settings' => [
                    'theme' => ['palette' => 'cyan', 'header_preset' => 'cyan-wave'],
                    'show_progress_bar' => true,
                ],
                'items' => [
                    [
                        'id' => 'sec_awal',
                        'label' => 'Bagian 1: Pemilihan Jalur Kegiatan',
                        'help' => 'Pilih jalur utama yang sedang Anda ikuti semester ini.',
                        'type' => 'section',
                        'required' => false,
                        'config' => ['after_action' => 'next'],
                    ],
                    [
                        'id' => 'jalur',
                        'label' => 'Jalur Kegiatan Semester Berjalan',
                        'help' => 'Jawaban Anda menentukan bagian formulir berikutnya.',
                        'type' => 'radio',
                        'required' => true,
                        'options' => ['Magang Industri', 'Penelitian Skripsi'],
                        'config' => [
                            'use_branching' => true,
                            'branching' => [
                                'Magang Industri' => 'sec_magang',
                                'Penelitian Skripsi' => 'sec_skripsi',
                            ],
                        ],
                    ],
                    [
                        'id' => 'sec_magang',
                        'label' => 'Bagian 2: Detail Tempat Magang',
                        'help' => 'Isi informasi mitra industri tempat Anda magang.',
                        'type' => 'section',
                        'required' => false,
                        'config' => ['after_action' => 'submit'],
                    ],
                    [
                        'id' => 'mitra',
                        'label' => 'Nama Perusahaan / Instansi Mitra',
                        'help' => '',
                        'type' => 'text',
                        'required' => true,
                        'config' => [],
                    ],
                    [
                        'id' => 'sec_skripsi',
                        'label' => 'Bagian 3: Detail Topik Skripsi',
                        'help' => 'Isi bidang fokus penelitian tugas akhir Anda.',
                        'type' => 'section',
                        'required' => false,
                        'config' => ['after_action' => 'submit'],
                    ],
                    [
                        'id' => 'topik_skripsi',
                        'label' => 'Judul / Topik Utama Skripsi',
                        'help' => '',
                        'type' => 'text',
                        'required' => true,
                        'config' => [],
                    ],
                ],
            ],
            [
                'slug' => 'kuis-evaluasi',
                'name' => 'Kuis / Tes Evaluasi Otomatis',
                'badge' => 'Mode Kuis',
                'desc' => 'Mode kuis dengan kunci jawaban, bobot poin otomatis, serta rilis nilai instan setelah kirim.',
                'icon' => 'graduation-cap',
                'default_title' => 'Evaluasi Pemahaman Tata Kelola Satu Data',
                'default_description' => 'Jawab seluruh pertanyaan berikut untuk menguji pemahaman standar kualitas data UNUSA.',
                'default_target' => 'Semua',
                'settings' => [
                    'theme' => ['palette' => 'emerald', 'header_preset' => 'emerald-mutu'],
                    'is_quiz' => true,
                    'release_grade' => 'immediately',
                    'quiz_show_missed' => true,
                    'quiz_show_correct' => true,
                    'quiz_show_points' => true,
                    'default_points' => 25,
                ],
                'items' => [
                    [
                        'id' => 'kuis_1',
                        'label' => 'Siapa yang bertugas melakukan validasi dan pre-processing sebelum dataset diterbitkan?',
                        'help' => '',
                        'type' => 'radio',
                        'required' => true,
                        'options' => ['Produsen Data', 'Wali Data', 'Pengguna Publik'],
                        'config' => [
                            'points' => 50,
                            'answer_key' => 'Wali Data',
                            'feedback_correct' => 'Tepat! Wali Data bertanggung jawab memeriksa, menormalisasi, dan memfinalisasi pre-processing.',
                            'feedback_wrong' => 'Belum tepat. Dalam tata kelola Satu Data, validasi dan pre-processing dilakukan oleh Wali Data.',
                        ],
                    ],
                    [
                        'id' => 'kuis_2',
                        'label' => 'Tahapan apa saja yang wajib diperiksa dalam gerbang Pre-processing Data?',
                        'help' => 'Pilih seluruh tahapan yang benar',
                        'type' => 'checkbox',
                        'required' => true,
                        'options' => ['Normalisasi Format', 'Deduplikasi', 'Penanganan Missing Value'],
                        'config' => [
                            'points' => 50,
                            'answer_key' => 'Normalisasi Format, Deduplikasi, Penanganan Missing Value',
                            'feedback_correct' => 'Sempurna! Ketiga tahap tersebut memastikan dataset bebas duplikat dan nilai kosong.',
                            'feedback_wrong' => 'Ketiga tahapan tersebut harus lengkap sebelum gerbang dataset dibuka.',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Load existing forms so Wali Data can import questions across instruments.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function importableFormsPayload(?int $excludeFormId = null): array
    {
        return DataForm::query()
            ->with(['category', 'kolomFormulir'])
            ->when($excludeFormId !== null, fn ($q) => $q->where('id', '!=', $excludeFormId))
            ->orderByDesc('created_at')
            ->limit(25)
            ->get()
            ->map(fn (DataForm $f): array => [
                'code' => $f->code,
                'title' => $f->title,
                'category' => $f->category?->name ?? 'Umum',
                'tahun' => $f->tahun,
                'items' => $f->items ?? [],
            ])
            ->values()
            ->all();
    }

    /**
     * Build Google Forms-style Responses summary, per-question distributions, and individual viewer data.
     *
     * @return array<string, mixed>
     */
    protected function buildResponsesPayload(DataForm $dataForm, DataQualityService $quality): array
    {
        $subs = $dataForm->submissions->sortByDesc('waktu_pengisian')->values();
        $fields = $dataForm->fields ?? [];

        $questionSummaries = [];
        foreach ($fields as $fld) {
            $fid = (string) ($fld['id'] ?? '');
            $type = (string) ($fld['type'] ?? 'text');
            $config = is_array($fld['config'] ?? null) ? $fld['config'] : [];

            $answers = [];
            $freq = [];
            $nums = [];

            foreach ($subs as $sub) {
                $val = trim((string) ($sub->values[$fid] ?? ''));
                if ($val === '') {
                    continue;
                }
                $answers[] = [
                    'sub_code' => $sub->code,
                    'user_name' => $sub->user?->name ?? 'Anonim',
                    'user_nim' => $sub->user?->nim ?? '-',
                    'value' => $val,
                    'at' => $sub->submittedFormatted(),
                ];

                if (in_array($type, ['radio', 'select', 'linear_scale', 'rating'], true)) {
                    $freq[$val] = ($freq[$val] ?? 0) + 1;
                } elseif ($type === 'checkbox') {
                    foreach (array_filter(array_map('trim', explode(',', $val))) as $tok) {
                        $freq[$tok] = ($freq[$tok] ?? 0) + 1;
                    }
                } elseif ($type === 'mc_grid' || $type === 'checkbox_grid') {
                    foreach ($quality->parseGridValue($val) as $rLabel => $cols) {
                        foreach ($cols as $cLabel) {
                            $key = "{$rLabel} → {$cLabel}";
                            $freq[$key] = ($freq[$key] ?? 0) + 1;
                        }
                    }
                }

                if (in_array($type, ['number', 'linear_scale', 'rating'], true)) {
                    $num = ChartPaletteService::parseNumeric($val);
                    if ($num !== null) {
                        $nums[] = $num;
                    }
                }
            }

            if ($type === 'linear_scale') {
                $min = (int) ($config['scale_min'] ?? 1);
                $max = (int) ($config['scale_max'] ?? 5);
                for ($n = $min; $n <= $max; $n++) {
                    if (! isset($freq[(string) $n])) {
                        $freq[(string) $n] = 0;
                    }
                }
                uksort($freq, fn (string $a, string $b): int => ((int) $a) <=> ((int) $b));
            } elseif ($type === 'rating') {
                $max = (int) ($config['rating_max'] ?? 5);
                for ($n = 1; $n <= $max; $n++) {
                    if (! isset($freq[(string) $n])) {
                        $freq[(string) $n] = 0;
                    }
                }
                uksort($freq, fn (string $a, string $b): int => ((int) $a) <=> ((int) $b));
            } else {
                foreach (($fld['options'] ?? []) as $opt) {
                    if (! isset($freq[(string) $opt])) {
                        $freq[(string) $opt] = 0;
                    }
                }
            }

            $chartSeries = [];
            foreach ($freq as $label => $cnt) {
                $chartSeries[] = ['n' => (string) $label, 'v' => (int) $cnt];
            }

            $questionSummaries[] = [
                'id' => $fid,
                'label' => (string) ($fld['label'] ?? $fid),
                'type' => $type,
                'answered_count' => count($answers),
                'avg' => ! empty($nums) ? round(array_sum($nums) / count($nums), 2) : null,
                'chart_kind' => in_array($type, ['radio', 'select'], true) ? 'donut' : (in_array($type, ['checkbox', 'linear_scale', 'rating', 'mc_grid', 'checkbox_grid'], true) ? 'bar' : null),
                'series' => $chartSeries,
                'answers' => array_slice($answers, 0, 50),
            ];
        }

        $individuals = $subs->map(fn (FormSubmission $sub): array => [
            'code' => $sub->code,
            'name' => $sub->user?->name ?? 'Responden',
            'nim' => $sub->user?->nim ?? '-',
            'at' => $sub->submittedFormatted(),
            'status' => $sub->status_validasi,
            'source' => $sub->sumber_input,
            'values' => $sub->values ?? [],
            'attachments' => $sub->attachments ?? [],
        ])->values()->all();

        return [
            'total' => $subs->count(),
            'valid' => $subs->where('status_validasi', 'valid')->count(),
            'mentah' => $subs->where('status_validasi', 'mentah')->count(),
            'ditolak' => $subs->where('status_validasi', 'ditolak')->count(),
            'accepting' => $dataForm->isOpen(),
            'questions' => $questionSummaries,
            'submissions' => $individuals,
        ];
    }

    /**
     * @return array{
     *     category_id: int,
     *     title: string,
     *     description: string,
     *     tahun: int,
     *     periode: string,
     *     target: string,
     *     batasi_satu_respon: bool,
     *     deadline: Carbon,
     *     settings: array<string, mixed>,
     *     fields: array<int, array<string, mixed>>
     * }
     */
    protected function validatePayload(Request $request, bool $isCreate): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['required', 'integer', 'exists:kategori,id'],
            'tahun' => ['required', 'digits:4'],
            'periode' => ['required', 'in:Ganjil,Genap'],
            'target' => ['required', 'in:Mahasiswa,Dosen,Semua'],
            'batasi_satu_respon' => ['nullable', 'boolean'],
            'deadline' => $isCreate ? ['required', 'date', 'after:now'] : ['required', 'date'],
            'fields_json' => ['required', 'string'],
            'settings_json' => ['nullable', 'string'],
        ];

        $messages = [
            'title.required' => 'Judul formulir wajib diisi.',
            'category_id.required' => 'Kategori data wajib dipilih.',
            'tahun.digits' => 'Tahun harus 4 digit.',
            'deadline.required' => 'Tenggat waktu wajib diisi.',
            'deadline.after' => 'Tenggat harus setelah waktu sekarang.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        $validated = $validator->validate();

        $rawFields = json_decode($validated['fields_json'], true);
        if (! is_array($rawFields) || empty($rawFields)) {
            $validator->errors()->add('fields_json', 'Tambahkan minimal satu pertanyaan.');
            throw new ValidationException($validator);
        }

        $cleanFields = [];
        $inputCount = 0;

        foreach ($rawFields as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $type = (string) ($item['type'] ?? 'text');
            if (! in_array($type, FormField::ALL_TYPES, true)) {
                $type = 'text';
            }

            $isInput = ! in_array($type, FormField::NON_INPUT_TYPES, true);
            $label = trim((string) ($item['label'] ?? ''));
            $fid = trim((string) ($item['id'] ?? '')) ?: ($isInput ? 'q'.($index + 1) : 'blk_'.($index + 1));
            $required = $isInput ? (bool) ($item['required'] ?? false) : false;
            $help = trim((string) ($item['help'] ?? ''));

            if ($isInput && $label === '') {
                $validator->errors()->add('fields_json', 'Label pertanyaan wajib diisi.');
                throw new ValidationException($validator);
            }

            if (! $isInput && $label === '') {
                $label = match ($type) {
                    'section' => 'Bagian Baru',
                    'title_block' => 'Judul & Keterangan',
                    'image_block' => 'Gambar Pendukung',
                    'video_block' => 'Video Penjelasan',
                    default => 'Blok Konten',
                };
            }

            if ($isInput) {
                $inputCount++;
            }

            $config = $this->sanitizeItemConfig($type, is_array($item['config'] ?? null) ? $item['config'] : []);

            $fieldData = [
                'id' => $fid,
                'label' => $label,
                'help' => $help,
                'type' => $type,
                'required' => $required,
                'config' => $config,
            ];

            if (in_array($type, ['select', 'radio', 'checkbox'], true)) {
                $optsRaw = $item['options'] ?? ($item['opt'] ?? []);
                $options = is_array($optsRaw)
                    ? array_values(array_filter(array_map('trim', array_map('strval', $optsRaw))))
                    : array_values(array_filter(array_map('trim', explode(',', (string) $optsRaw))));

                if (empty($options)) {
                    $validator->errors()->add('fields_json', "Pertanyaan pilihan \"{$label}\" wajib memiliki minimal satu opsi.");
                    throw new ValidationException($validator);
                }
                $fieldData['options'] = $options;
            }

            if (in_array($type, ['mc_grid', 'checkbox_grid'], true)) {
                if (empty($config['grid_rows']) || empty($config['grid_cols'])) {
                    $validator->errors()->add('fields_json', "Pertanyaan kisi \"{$label}\" wajib memiliki minimal 1 baris dan 1 kolom.");
                    throw new ValidationException($validator);
                }
            }

            $cleanFields[] = $fieldData;
        }

        if ($inputCount === 0) {
            $validator->errors()->add('fields_json', 'Formulir wajib memiliki minimal satu pertanyaan input.');
            throw new ValidationException($validator);
        }

        $settings = $this->sanitizeFormSettings($request->input('settings_json'));

        return [
            'category_id' => (int) $validated['category_id'],
            'title' => trim($validated['title']),
            'description' => trim((string) ($validated['description'] ?? '')),
            'tahun' => (int) $validated['tahun'],
            'periode' => $validated['periode'],
            'target' => $validated['target'],
            'batasi_satu_respon' => $request->boolean('batasi_satu_respon'),
            'deadline' => Carbon::parse($validated['deadline']),
            'settings' => $settings,
            'fields' => $cleanFields,
        ];
    }

    /**
     * Sanitize per-item Google Forms configuration payload.
     *
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    protected function sanitizeItemConfig(string $type, array $raw): array
    {
        $cfg = [];

        // Image / media attachment on question or block
        $imageUrl = $this->sanitizeMediaUrl((string) ($raw['image_url'] ?? ''));
        if ($imageUrl !== '') {
            $cfg['image_url'] = $imageUrl;
            $cfg['image_caption'] = trim((string) ($raw['image_caption'] ?? ''));
            $cfg['image_align'] = in_array($raw['image_align'] ?? 'center', ['left', 'center', 'right'], true)
                ? (string) $raw['image_align']
                : 'center';
        }

        if ($type === 'video_block') {
            $videoUrl = trim((string) ($raw['video_url'] ?? ''));
            if ($videoUrl !== '' && preg_match('#^https?://#i', $videoUrl) === 1) {
                $cfg['video_url'] = $videoUrl;
            }
            $cfg['video_caption'] = trim((string) ($raw['video_caption'] ?? ''));
        }

        if ($type === 'section') {
            $after = trim((string) ($raw['after_action'] ?? 'next'));
            $cfg['after_action'] = $after !== '' ? $after : 'next';
        }

        if (in_array($type, ['radio', 'checkbox', 'select'], true)) {
            if (! empty($raw['shuffle_options'])) {
                $cfg['shuffle_options'] = true;
            }
            if ($type !== 'select' && ! empty($raw['allow_other'])) {
                $cfg['allow_other'] = true;
            }
            if (! empty($raw['option_images']) && is_array($raw['option_images'])) {
                $optImages = [];
                foreach ($raw['option_images'] as $optName => $imgUrl) {
                    $cleanImg = $this->sanitizeMediaUrl((string) $imgUrl);
                    if ($cleanImg !== '') {
                        $optImages[(string) $optName] = $cleanImg;
                    }
                }
                if (! empty($optImages)) {
                    $cfg['option_images'] = $optImages;
                }
            }
            if (in_array($type, ['radio', 'select'], true) && ! empty($raw['use_branching'])) {
                $cfg['use_branching'] = true;
                $branchMap = [];
                if (is_array($raw['branching'] ?? null)) {
                    foreach ($raw['branching'] as $optLabel => $targetSection) {
                        $t = trim((string) $targetSection);
                        if ($t !== '') {
                            $branchMap[(string) $optLabel] = $t;
                        }
                    }
                }
                $cfg['branching'] = $branchMap;
            }
        }

        if ($type === 'linear_scale') {
            $min = in_array((int) ($raw['scale_min'] ?? 1), [0, 1], true) ? (int) ($raw['scale_min'] ?? 1) : 1;
            $max = max(2, min(10, (int) ($raw['scale_max'] ?? 5)));
            if ($max <= $min) {
                $max = $min + 1;
            }
            $cfg['scale_min'] = $min;
            $cfg['scale_max'] = $max;
            $cfg['scale_min_label'] = mb_substr(trim((string) ($raw['scale_min_label'] ?? '')), 0, 80);
            $cfg['scale_max_label'] = mb_substr(trim((string) ($raw['scale_max_label'] ?? '')), 0, 80);
        }

        if ($type === 'rating') {
            $cfg['rating_max'] = max(3, min(10, (int) ($raw['rating_max'] ?? 5)));
            $cfg['rating_icon'] = in_array($raw['rating_icon'] ?? 'star', ['star', 'heart', 'thumb'], true)
                ? (string) $raw['rating_icon']
                : 'star';
        }

        if (in_array($type, ['mc_grid', 'checkbox_grid'], true)) {
            $rows = is_array($raw['grid_rows'] ?? null)
                ? array_values(array_filter(array_map('trim', array_map('strval', $raw['grid_rows']))))
                : [];
            $cols = is_array($raw['grid_cols'] ?? null)
                ? array_values(array_filter(array_map('trim', array_map('strval', $raw['grid_cols']))))
                : [];
            $cfg['grid_rows'] = $rows;
            $cfg['grid_cols'] = $cols;
            $cfg['require_row_response'] = (bool) ($raw['require_row_response'] ?? false);
            $cfg['limit_one_per_col'] = (bool) ($raw['limit_one_per_col'] ?? false);
            if (! empty($raw['shuffle_rows'])) {
                $cfg['shuffle_rows'] = true;
            }
        }

        if ($type === 'date') {
            $cfg['include_time'] = (bool) ($raw['include_time'] ?? false);
            $cfg['include_year'] = array_key_exists('include_year', $raw) ? (bool) $raw['include_year'] : true;
        }

        if ($type === 'time') {
            $cfg['time_mode'] = ($raw['time_mode'] ?? 'time') === 'duration' ? 'duration' : 'time';
        }

        if ($type === 'file') {
            $restrict = (bool) ($raw['restrict_file_types'] ?? false);
            $fileTypes = [];
            if ($restrict && is_array($raw['file_types'] ?? null)) {
                $allowedGroups = array_keys(DataQualityService::FILE_TYPE_EXTENSIONS);
                $fileTypes = array_values(array_intersect(
                    array_map('strtolower', array_map('strval', $raw['file_types'])),
                    $allowedGroups
                ));
            }
            $cfg['restrict_file_types'] = $restrict && ! empty($fileTypes);
            if ($cfg['restrict_file_types']) {
                $cfg['file_types'] = $fileTypes;
            }
            $cfg['file_max_count'] = max(1, min(20, (int) ($raw['file_max_count'] ?? 1)));
            $rawMaxMb = array_key_exists('file_max_mb', $raw) ? (int) $raw['file_max_mb'] : 0;
            $cfg['file_max_mb'] = $rawMaxMb <= 0 ? 0 : min(1024, $rawMaxMb);
        }

        if (is_array($raw['validation'] ?? null) && ! empty($raw['validation']['enabled'])) {
            $v = $raw['validation'];
            $cfg['validation'] = [
                'enabled' => true,
                'category' => in_array($v['category'] ?? 'text', ['number', 'text', 'length', 'regex', 'checkbox'], true)
                    ? (string) $v['category']
                    : 'text',
                'operator' => mb_substr(trim((string) ($v['operator'] ?? '')), 0, 30),
                'value' => mb_substr((string) ($v['value'] ?? ''), 0, 200),
                'value2' => mb_substr((string) ($v['value2'] ?? ''), 0, 200),
                'message' => mb_substr(trim((string) ($v['message'] ?? '')), 0, 200),
            ];
        }

        // Quiz / Answer Key metadata
        $pts = max(0, min(1000, (int) ($raw['points'] ?? 0)));
        $ansKey = trim((string) ($raw['answer_key'] ?? ''));
        if ($pts > 0 || $ansKey !== '') {
            $cfg['points'] = $pts;
            $cfg['answer_key'] = $ansKey;
            $cfg['feedback_correct'] = mb_substr(trim((string) ($raw['feedback_correct'] ?? '')), 0, 500);
            $cfg['feedback_wrong'] = mb_substr(trim((string) ($raw['feedback_wrong'] ?? '')), 0, 500);
        }

        return $cfg;
    }

    /**
     * Sanitize form-level settings JSON against UNUSA defaults.
     *
     * @return array<string, mixed>
     */
    protected function sanitizeFormSettings(?string $settingsJson): array
    {
        $defaults = DataForm::defaultSettings();
        if (! is_string($settingsJson) || trim($settingsJson) === '') {
            return $defaults;
        }

        $raw = json_decode($settingsJson, true);
        if (! is_array($raw)) {
            return $defaults;
        }

        $themeRaw = is_array($raw['theme'] ?? null) ? $raw['theme'] : [];
        $rawPalette = (string) ($themeRaw['palette'] ?? 'cerulean');
        $rawBgTone = (string) ($themeRaw['bg_tone'] ?? 'soft');
        $rawHeaderPreset = (string) ($themeRaw['header_preset'] ?? 'cerulean-grid');
        $rawFontFamily = (string) ($themeRaw['font_family'] ?? 'jakarta');

        $palette = in_array($rawPalette, ['cerulean', 'benhur', 'cyan', 'emerald', 'slate'], true)
            ? $rawPalette
            : 'cerulean';
        $bgTone = in_array($rawBgTone, ['soft', 'tint', 'paper', 'slate'], true)
            ? $rawBgTone
            : 'soft';
        $headerPreset = in_array($rawHeaderPreset, ['cerulean-grid', 'benhur-arch', 'cyan-wave', 'emerald-mutu', 'slate-formal', 'minimal'], true)
            ? $rawHeaderPreset
            : 'cerulean-grid';
        $fontFamily = in_array($rawFontFamily, ['jakarta', 'mono', 'serif'], true)
            ? $rawFontFamily
            : 'jakarta';

        return [
            'theme' => [
                'palette' => $palette,
                'bg_tone' => $bgTone,
                'header_preset' => $headerPreset,
                'header_image' => $this->sanitizeMediaUrl((string) ($themeRaw['header_image'] ?? '')),
                'font_family' => $fontFamily,
                'header_size' => max(18, min(32, (int) ($themeRaw['header_size'] ?? 24))),
                'question_size' => max(13, min(20, (int) ($themeRaw['question_size'] ?? 15))),
                'text_size' => max(11, min(16, (int) ($themeRaw['text_size'] ?? 13))),
            ],
            'is_quiz' => (bool) ($raw['is_quiz'] ?? false),
            'release_grade' => ($raw['release_grade'] ?? 'immediately') === 'later' ? 'later' : 'immediately',
            'quiz_show_missed' => array_key_exists('quiz_show_missed', $raw) ? (bool) $raw['quiz_show_missed'] : true,
            'quiz_show_correct' => array_key_exists('quiz_show_correct', $raw) ? (bool) $raw['quiz_show_correct'] : true,
            'quiz_show_points' => array_key_exists('quiz_show_points', $raw) ? (bool) $raw['quiz_show_points'] : true,
            'default_points' => max(0, min(100, (int) ($raw['default_points'] ?? 10))),
            'collect_identity' => array_key_exists('collect_identity', $raw) ? (bool) $raw['collect_identity'] : true,
            'allow_edit_after_submit' => (bool) ($raw['allow_edit_after_submit'] ?? false),
            'closed_message' => mb_substr(trim((string) ($raw['closed_message'] ?? $defaults['closed_message'])), 0, 500),
            'show_progress_bar' => array_key_exists('show_progress_bar', $raw) ? (bool) $raw['show_progress_bar'] : true,
            'shuffle_questions' => (bool) ($raw['shuffle_questions'] ?? false),
            'confirmation_message' => mb_substr(trim((string) ($raw['confirmation_message'] ?? $defaults['confirmation_message'])), 0, 500),
            'show_another_response_link' => array_key_exists('show_another_response_link', $raw) ? (bool) $raw['show_another_response_link'] : true,
            'show_summary_after_submit' => (bool) ($raw['show_summary_after_submit'] ?? false),
            'default_required' => (bool) ($raw['default_required'] ?? false),
        ];
    }

    protected function sanitizeMediaUrl(string $url): string
    {
        $clean = trim($url);
        if ($clean === '') {
            return '';
        }
        if (str_starts_with($clean, '/') || preg_match('#^https?://#i', $clean) === 1) {
            return mb_substr($clean, 0, 500);
        }

        return '';
    }
}
