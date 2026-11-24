<?php

namespace App\Services;

use App\Models\Category;
use App\Models\DataForm;
use App\Models\Dataset;
use App\Models\FormSubmission;
use App\Models\SubmissionValue;
use App\Models\User;
use App\Models\Visualization;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use ZipArchive;

class AmiWorkbookImporter
{
    public const DEFAULT_WORKBOOK_PATH = 'database/data/ami_snptu_si_unusa.xlsx';

    public const DEFAULT_SNAPSHOT_PATH = 'database/data/ami_snptu_parsed.json';

    private const SHEET_MAIN_28 = '8 Pendidikan + 3 Penelitian + 3';

    private const SHEET_BENCHMARK_38 = 'Target 24';

    private const SHEET_REVIEW_PRODI = 'After review Prodi';

    private const SHEET_PTK_BORANG = 'PTK Borang LAM';

    private const SHEET_PTK_28 = 'PTK Platform 28 Standar';

    private const SHEET_PTK_BANK = 'PTK Bank Dokumen';

    private const SHEET_SARAN = 'Saran Pengembangan';

    private const SHEET_TILIK = 'Daftar Tilik';

    /**
     * @var array<int, string>
     */
    public const BENCHMARK_PRODI_LIST = [
        'S1 Keperawatan',
        'DIII Keperawatan',
        'DIII Kebidanan',
        'S1 Kebidanan',
        'S2 Keperawatan',
        'S1 Kesehatan Masyarakat',
        'S1 Gizi',
        'DIV Analis Kesehatan',
        'DIV K3',
        'S1 Pendidikan Dokter',
        'S1 Manajemen',
        'S1 Akuntansi',
        'S1 Sistem Informasi',
        'S1 Pendidikan Bahasa Inggris',
        'S1 PGPAUD',
        'S1 PGSD',
    ];

    /**
     * @var array<string, array<string, mixed>>
     */
    private static array $memoryCache = [];

    /**
     * Parse the AMI OOXML workbook or load from a newer pre-parsed JSON snapshot.
     *
     * @return array{
     *     indicators: array<int, array<string, mixed>>,
     *     standard_summaries: array<int, array<string, mixed>>,
     *     benchmarks: array<int, array<string, mixed>>,
     *     ptk_findings: array<int, array<string, mixed>>,
     *     recommendations: array<int, array<string, mixed>>,
     *     audit_checklist_fields: array<int, array<string, mixed>>
     * }
     */
    public function parseWithSnapshot(string $xlsxPath, ?string $snapshotPath = null): array
    {
        if (
            $snapshotPath !== null
            && is_file($snapshotPath)
            && (! is_file($xlsxPath) || filemtime($snapshotPath) >= filemtime($xlsxPath))
        ) {
            $cacheKey = 'json:'.realpath($snapshotPath).':'.filemtime($snapshotPath);
            if (isset(self::$memoryCache[$cacheKey])) {
                return self::$memoryCache[$cacheKey];
            }

            $decoded = json_decode((string) file_get_contents($snapshotPath), true);
            if (is_array($decoded) && isset($decoded['indicators'], $decoded['standard_summaries'])) {
                self::$memoryCache[$cacheKey] = $decoded;

                return $decoded;
            }
        }

        return $this->parseWorkbook($xlsxPath);
    }

    /**
     * Parse the AMI OOXML workbook directly via ZipArchive + DOMDocument.
     *
     * @return array{
     *     indicators: array<int, array<string, mixed>>,
     *     standard_summaries: array<int, array<string, mixed>>,
     *     benchmarks: array<int, array<string, mixed>>,
     *     ptk_findings: array<int, array<string, mixed>>,
     *     recommendations: array<int, array<string, mixed>>,
     *     audit_checklist_fields: array<int, array<string, mixed>>
     * }
     */
    public function parseWorkbook(string $xlsxPath): array
    {
        if (! is_file($xlsxPath)) {
            throw new RuntimeException("Workbook AMI tidak ditemukan: {$xlsxPath}");
        }

        $cacheKey = 'xlsx:'.realpath($xlsxPath).':'.filemtime($xlsxPath);
        if (isset(self::$memoryCache[$cacheKey])) {
            return self::$memoryCache[$cacheKey];
        }

        $zip = new ZipArchive;
        if ($zip->open($xlsxPath) !== true) {
            throw new RuntimeException("Gagal membuka berkas XLSX AMI: {$xlsxPath}");
        }

        try {
            $sharedStrings = $this->readSharedStrings($zip);
            $sheetTargets = $this->readSheetTargets($zip);

            $mainRows = $this->readSheetRows($zip, $sheetTargets[self::SHEET_MAIN_28] ?? '', $sharedStrings);
            $reviewRows = $this->readSheetRows($zip, $sheetTargets[self::SHEET_REVIEW_PRODI] ?? '', $sharedStrings);
            $benchmarkRows = $this->readSheetRows($zip, $sheetTargets[self::SHEET_BENCHMARK_38] ?? '', $sharedStrings);
            $ptkBorangRows = $this->readSheetRows($zip, $sheetTargets[self::SHEET_PTK_BORANG] ?? '', $sharedStrings);
            $ptk28Rows = $this->readSheetRows($zip, $sheetTargets[self::SHEET_PTK_28] ?? '', $sharedStrings);
            $ptkBankRows = $this->readSheetRows($zip, $sheetTargets[self::SHEET_PTK_BANK] ?? '', $sharedStrings);
            $saranRows = $this->readSheetRows($zip, $sheetTargets[self::SHEET_SARAN] ?? '', $sharedStrings);
            $tilikRows = $this->readSheetRows($zip, $sheetTargets[self::SHEET_TILIK] ?? '', $sharedStrings);
        } finally {
            $zip->close();
        }

        $indicators = $this->parseMainIndicators($mainRows);
        $standardSummaries = $this->buildStandardSummaries($indicators);
        $reviewNotesMap = $this->extractReviewNotes($reviewRows);
        $benchmarks = $this->parseBenchmarks($benchmarkRows, $reviewNotesMap);

        $ptkFindings = array_merge(
            $this->parsePtkStandardSheet($ptkBorangRows, 'borang_lam', '2019-2020'),
            $this->parsePtkStandardSheet($ptk28Rows, 'standar_28', '2018-2019', count($ptkBorangRows)),
            $this->parsePtkBankDokumen($ptkBankRows, 1000)
        );
        foreach ($ptkFindings as $idx => &$item) {
            $item['sort_order'] = $idx + 1;
        }
        unset($item);

        $recommendations = $this->parseRecommendations($saranRows);
        $auditChecklistFields = $this->buildChecklistFormFields($tilikRows);

        $result = [
            'indicators' => $indicators,
            'standard_summaries' => $standardSummaries,
            'benchmarks' => $benchmarks,
            'ptk_findings' => $ptkFindings,
            'recommendations' => $recommendations,
            'audit_checklist_fields' => $auditChecklistFields,
        ];

        self::$memoryCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Parse workbook and export JSON snapshot for fast deterministic seeding.
     *
     * @return array<string, int>
     */
    public function exportParsedJson(string $xlsxPath, string $jsonPath): array
    {
        $parsed = $this->parseWorkbook($xlsxPath);
        $dir = dirname($jsonPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            $jsonPath,
            json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return [
            'indicators' => count($parsed['indicators']),
            'standard_summaries' => count($parsed['standard_summaries']),
            'benchmarks' => count($parsed['benchmarks']),
            'ptk_findings' => count($parsed['ptk_findings']),
            'recommendations' => count($parsed['recommendations']),
        ];
    }

    /**
     * Persist parsed AMI Excel data into DPPL forms, normalized form submissions, public datasets, and dashboard charts.
     *
     * @param  array<string, Category>  $categories
     * @return array<string, int>
     */
    public function importIntoDatabase(
        string $xlsxPath,
        ?User $creator = null,
        ?string $snapshotPath = null,
        array $categories = [],
        bool $clearExisting = true
    ): array {
        $parsed = $this->parseWithSnapshot($xlsxPath, $snapshotPath);
        $quality = app(DataQualityService::class);

        return DB::transaction(function () use ($parsed, $creator, $categories, $clearExisting, $quality): array {
            $creatorId = $creator?->id
                ?? User::withRole('wali')->value('id')
                ?? User::withRole('admin')->value('id');

            $dosenSubmitterId = User::query()->where('nim', '0712048601')->value('id') ?? $creatorId;

            $amiCategory = $categories['KT-AMI-01'] ?? Category::query()->firstOrCreate(
                ['code' => 'KT-AMI-01'],
                [
                    'nama_kategori' => 'Audit Mutu Internal (AMI)',
                    'deskripsi' => 'Instrumen SNPT-U 28 & 38 standar, benchmark 16 prodi UNUSA, Permintaan Tindakan Koreksi (PTK), dan saran pengembangan Prodi S1 Sistem Informasi.',
                ]
            );

            $formsMap = $this->upsertAmiForms($parsed['audit_checklist_fields'], $amiCategory, $creatorId);

            if ($clearExisting) {
                $formIds = array_map(fn (DataForm $f): int => $f->id, [
                    $formsMap['F-06'],
                    $formsMap['F-07'],
                    $formsMap['F-08'],
                ]);
                FormSubmission::query()->whereIn('id_formulir', $formIds)->delete();
            }

            $dataIsianCount = 0;
            $dataIsianCount += $this->bulkImportFormRows(
                $formsMap['F-06'],
                $this->mapIndicatorSubmissionRows($parsed['indicators']),
                'S-AMI-IND-',
                $dosenSubmitterId,
                $quality
            );
            $dataIsianCount += $this->bulkImportFormRows(
                $formsMap['F-07'],
                $this->mapPtkSubmissionRows($parsed['ptk_findings']),
                'S-AMI-PTK-',
                $dosenSubmitterId,
                $quality
            );
            $dataIsianCount += $this->bulkImportFormRows(
                $formsMap['F-08'],
                $this->mapRecommendationSubmissionRows($parsed['recommendations']),
                'S-AMI-SRN-',
                $dosenSubmitterId,
                $quality
            );

            $datasetsMap = $this->upsertAmiDatasets($parsed, $amiCategory, $formsMap, $creatorId);
            $vizCount = $this->upsertAmiVisualizations($datasetsMap, $creatorId);

            return [
                'forms' => count($formsMap),
                'indicators_submissions' => count($parsed['indicators']),
                'ptk_submissions' => count($parsed['ptk_findings']),
                'recommendation_submissions' => count($parsed['recommendations']),
                'data_isian' => $dataIsianCount,
                'datasets' => count($datasetsMap),
                'visualizations' => $vizCount,
            ];
        });
    }

    /**
     * Create/update the 4 AMI DPPL formulir definitions (F-05..F-08) and their normalized kolom_formulir.
     *
     * @param  array<int, array<string, mixed>>  $checklistFields
     * @return array<string, DataForm>
     */
    protected function upsertAmiForms(array $checklistFields, Category $amiCategory, ?int $creatorId): array
    {
        $specs = [
            [
                'code' => 'F-05',
                'judul' => 'Daftar Tilik Audit Mutu Internal (LAM & 28 Standar)',
                'deskripsi' => 'Instrumen pencatatan pertanyaan auditor, hasil observasi/visitasi, kesesuaian (S/TS), catatan khusus, saran, dan kesimpulan AMI Prodi S1 Sistem Informasi.',
                'tahun' => 2026,
                'periode' => 'Ganjil',
                'target' => 'Dosen',
                'fields' => $checklistFields,
            ],
            [
                'code' => 'F-06',
                'judul' => 'Formulir Input Instrumen AMI 28 Standar SNPT-U 2025/2026',
                'deskripsi' => 'Formulir pengumpulan capaian indikator mutu 28 Standar SNPT-U Prodi S1 Sistem Informasi (sumber impor GForm/Excel AMI).',
                'tahun' => 2025,
                'periode' => 'Ganjil',
                'target' => 'Dosen',
                'fields' => [
                    ['id' => 'q1', 'label' => 'Kode Indikator', 'type' => 'text', 'required' => true],
                    ['id' => 'q2', 'label' => 'Kelompok Standar', 'type' => 'text', 'required' => true],
                    ['id' => 'q3', 'label' => 'Nomor Standar', 'type' => 'number', 'required' => true],
                    ['id' => 'q4', 'label' => 'Nama Standar Mutu', 'type' => 'text', 'required' => true],
                    ['id' => 'q5', 'label' => 'Nomor Butir', 'type' => 'text', 'required' => false],
                    ['id' => 'q6', 'label' => 'Indikator Mutu', 'type' => 'textarea', 'required' => true],
                    ['id' => 'q7', 'label' => 'Baseline 2023', 'type' => 'text', 'required' => false],
                    ['id' => 'q8', 'label' => 'Baseline 2024', 'type' => 'text', 'required' => false],
                    ['id' => 'q9', 'label' => 'Target Akhir', 'type' => 'text', 'required' => false],
                    ['id' => 'q10', 'label' => 'Realisasi', 'type' => 'text', 'required' => false],
                    ['id' => 'q11', 'label' => 'Link Bukti Dokumen', 'type' => 'text', 'required' => false],
                    ['id' => 'q12', 'label' => 'Konfirmasi Auditor (S/TS)', 'type' => 'text', 'required' => false],
                    ['id' => 'q13', 'label' => 'Status Capaian', 'type' => 'text', 'required' => false],
                    ['id' => 'q14', 'label' => 'Nilai Capaian', 'type' => 'text', 'required' => false],
                ],
            ],
            [
                'code' => 'F-07',
                'judul' => 'Formulir Permintaan Tindakan Koreksi (PTK) AMI 2019-2025',
                'deskripsi' => 'Formulir pelaporan temuan PTK Borang LAM, 28 Standar, dan Bank Dokumen beserta analisis penyebab dan tindak lanjut.',
                'tahun' => 2025,
                'periode' => 'Ganjil',
                'target' => 'Dosen',
                'fields' => [
                    ['id' => 'q1', 'label' => 'Kode Temuan', 'type' => 'text', 'required' => true],
                    ['id' => 'q2', 'label' => 'Jenis PTK', 'type' => 'select', 'required' => true, 'options' => ['PTK Borang LAM', 'PTK 28 Standar', 'PTK Bank Dokumen']],
                    ['id' => 'q3', 'label' => 'Tahun Akademik', 'type' => 'text', 'required' => true],
                    ['id' => 'q4', 'label' => 'No Temuan', 'type' => 'text', 'required' => false],
                    ['id' => 'q5', 'label' => 'Kriteria / Standar', 'type' => 'text', 'required' => false],
                    ['id' => 'q6', 'label' => 'Tanggal Temuan', 'type' => 'text', 'required' => false],
                    ['id' => 'q7', 'label' => 'Uraian Temuan', 'type' => 'textarea', 'required' => true],
                    ['id' => 'q8', 'label' => 'Kategori', 'type' => 'text', 'required' => false],
                    ['id' => 'q9', 'label' => 'Analisis Penyebab', 'type' => 'textarea', 'required' => false],
                    ['id' => 'q10', 'label' => 'Tindakan Koreksi / Pencegahan', 'type' => 'textarea', 'required' => false],
                    ['id' => 'q11', 'label' => 'Rencana Selesai', 'type' => 'text', 'required' => false],
                    ['id' => 'q12', 'label' => 'Status Tindak Lanjut', 'type' => 'text', 'required' => false],
                    ['id' => 'q13', 'label' => 'Link Bukti Dokumen', 'type' => 'text', 'required' => false],
                ],
            ],
            [
                'code' => 'F-08',
                'judul' => 'Formulir Saran Pengembangan & Rekomendasi Audit AMI 2025/2026',
                'deskripsi' => 'Formulir catatan kelebihan dan peluang peningkatan mutu hasil Audit Mutu Internal Prodi S1 Sistem Informasi.',
                'tahun' => 2026,
                'periode' => 'Ganjil',
                'target' => 'Dosen',
                'fields' => [
                    ['id' => 'q1', 'label' => 'No Urut', 'type' => 'number', 'required' => true],
                    ['id' => 'q2', 'label' => 'Aspek / Bidang', 'type' => 'text', 'required' => true],
                    ['id' => 'q3', 'label' => 'Kelebihan', 'type' => 'textarea', 'required' => true],
                    ['id' => 'q4', 'label' => 'Peluang untuk Peningkatan', 'type' => 'textarea', 'required' => true],
                    ['id' => 'q5', 'label' => 'Tanggal Audit', 'type' => 'text', 'required' => false],
                ],
            ],
        ];

        $forms = [];
        foreach ($specs as $spec) {
            $form = DataForm::query()->updateOrCreate(
                ['code' => $spec['code']],
                [
                    'id_kategori' => $amiCategory->id,
                    'judul' => $spec['judul'],
                    'deskripsi' => $spec['deskripsi'],
                    'tanggal_waktu' => now()->addDays(45),
                    'status_aktif' => true,
                    'tahun' => $spec['tahun'],
                    'periode' => $spec['periode'],
                    'target' => $spec['target'],
                    'dibuat_oleh' => $creatorId,
                ]
            );
            $form->syncFields($spec['fields']);
            $forms[$spec['code']] = $form;
        }

        return $forms;
    }

    /**
     * Bulk insert form submissions and normalized data_isian rows in small SQL-safe chunks.
     *
     * @param  array<int, array<string, string>>  $rows
     */
    protected function bulkImportFormRows(
        DataForm $form,
        array $rows,
        string $codePrefix,
        ?int $submitterId,
        DataQualityService $quality
    ): int {
        if (empty($rows)) {
            return 0;
        }

        $form->loadMissing('kolomFormulir');
        $fieldsByCode = [];
        foreach ($form->kolomFormulir as $col) {
            $fieldsByCode[$col->kode_kolom] = [
                'model' => $col,
                'def' => $col->toFieldArray(),
            ];
        }

        $now = now();
        $submissionInserts = [];
        $valuesByCode = [];

        foreach (array_values($rows) as $idx => $rowValues) {
            $subCode = sprintf('%s%03d', $codePrefix, $idx + 1);
            $submissionInserts[] = [
                'code' => $subCode,
                'id_formulir' => $form->id,
                'diisi_oleh' => $submitterId,
                'waktu_pengisian' => $now,
                'jalur_berkas_bukti' => null,
                'dibersihkan_oleh' => null,
                'waktu_dibersihkan' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $valuesByCode[$subCode] = $rowValues;
        }

        foreach (array_chunk($submissionInserts, 100) as $chunk) {
            FormSubmission::query()->insert($chunk);
        }

        $submissionIds = FormSubmission::query()
            ->where('id_formulir', $form->id)
            ->pluck('id', 'code')
            ->all();

        $dataIsianInserts = [];
        foreach ($valuesByCode as $subCode => $rowValues) {
            $subId = $submissionIds[$subCode] ?? null;
            if ($subId === null) {
                continue;
            }

            foreach ($fieldsByCode as $kodeKolom => $entry) {
                $raw = (string) ($rowValues[$kodeKolom] ?? '');
                $normalized = $quality->normalizeValue($entry['def'], $raw);
                $dataIsianInserts[] = [
                    'id_pengisian' => $subId,
                    'id_kolom' => $entry['model']->id,
                    'nilai' => $normalized,
                    'status_bersih' => true,
                    'jalur_berkas' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($dataIsianInserts, 100) as $chunk) {
            SubmissionValue::query()->insert($chunk);
        }

        return count($dataIsianInserts);
    }

    /**
     * @param  array<int, array<string, mixed>>  $indicators
     * @return array<int, array<string, string>>
     */
    protected function mapIndicatorSubmissionRows(array $indicators): array
    {
        return array_map(static fn (array $r): array => [
            'q1' => (string) $r['code'],
            'q2' => (string) $r['kelompok_standar'],
            'q3' => (string) $r['standar_nomor'],
            'q4' => (string) $r['standar_nama'],
            'q5' => (string) ($r['no_butir'] ?? ''),
            'q6' => (string) $r['indikator'],
            'q7' => (string) ($r['baseline_2023'] ?? ''),
            'q8' => (string) ($r['baseline_2024'] ?? ''),
            'q9' => (string) ($r['target_akhir'] ?? ''),
            'q10' => (string) ($r['realisasi'] ?? ''),
            'q11' => (string) ($r['link_bukti'] ?? ''),
            'q12' => (string) ($r['konfirmasi_auditor'] ?? ''),
            'q13' => (string) ($r['status'] ?? ''),
            'q14' => $r['capaian'] !== null ? (string) round((float) $r['capaian'], 4) : '',
        ], $indicators);
    }

    /**
     * @param  array<int, array<string, mixed>>  $ptkFindings
     * @return array<int, array<string, string>>
     */
    protected function mapPtkSubmissionRows(array $ptkFindings): array
    {
        $jenisLabel = static fn (string $jenis): string => match ($jenis) {
            'borang_lam' => 'PTK Borang LAM',
            'standar_28' => 'PTK 28 Standar',
            'bank_dokumen' => 'PTK Bank Dokumen',
            default => 'PTK 28 Standar',
        };

        $out = [];
        foreach (array_values($ptkFindings) as $idx => $p) {
            $out[] = [
                'q1' => sprintf('PTK-%03d', $idx + 1),
                'q2' => $jenisLabel((string) $p['jenis_ptk']),
                'q3' => trim((string) ($p['tahun_akademik'] ?? '')) ?: '2023-2024',
                'q4' => (string) ($p['no_temuan'] ?? ''),
                'q5' => (string) ($p['kriteria_atau_standar'] ?? ''),
                'q6' => (string) ($p['tanggal_temuan'] ?? ''),
                'q7' => trim((string) ($p['uraian'] ?? '')) ?: '-',
                'q8' => (string) ($p['kategori'] ?? ''),
                'q9' => (string) ($p['analisis_penyebab'] ?? ''),
                'q10' => (string) ($p['tindakan_koreksi'] ?? ''),
                'q11' => (string) ($p['rencana_selesai'] ?? ''),
                'q12' => (string) ($p['status'] ?? ''),
                'q13' => (string) ($p['link_bukti'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, array<string, mixed>>  $recommendations
     * @return array<int, array<string, string>>
     */
    protected function mapRecommendationSubmissionRows(array $recommendations): array
    {
        return array_map(static fn (array $r): array => [
            'q1' => (string) $r['no_urut'],
            'q2' => trim((string) $r['aspek_bidang']) ?: 'Umum',
            'q3' => trim((string) $r['kelebihan']) ?: '-',
            'q4' => trim((string) $r['peluang_peningkatan']) ?: '-',
            'q5' => (string) ($r['tanggal_audit'] ?? 'Surabaya, 21 September 2026'),
        ], $recommendations);
    }

    /**
     * Build and upsert the 10 structured AMI datasets (DS-13 .. DS-22) linked via id_formulir_sumber.
     *
     * @param  array<string, mixed>  $parsed
     * @param  array<string, DataForm>  $formsMap
     * @return array<string, Dataset>
     */
    protected function upsertAmiDatasets(
        array $parsed,
        Category $amiCategory,
        array $formsMap,
        ?int $creatorId
    ): array {
        $indicators = $parsed['indicators'];
        $summaries = $parsed['standard_summaries'];
        $benchmarks = $parsed['benchmarks'];
        $ptkFindings = $parsed['ptk_findings'];
        $recommendations = $parsed['recommendations'];

        $indicatorCols = [
            'Kode',
            'Kelompok Standar',
            'No Standar',
            'Standar Mutu',
            'No Butir',
            'Indikator Mutu',
            'Baseline 2023',
            'Baseline 2024',
            'Keterangan Renstra',
            'Pengukuran',
            'Target Universitas',
            'Target Renop Prodi',
            'Target Akhir',
            'Catatan Perhitungan Auditee',
            'Realisasi',
            'Link Bukti Dokumen',
            'Konfirmasi (S/TS)',
            'Catatan Auditor',
            'Status',
            'Capaian',
        ];

        $formatIndicatorRow = static fn (array $r): array => [
            (string) $r['code'],
            (string) $r['kelompok_standar'],
            (int) $r['standar_nomor'],
            (string) $r['standar_nama'],
            (string) ($r['no_butir'] ?? ''),
            (string) $r['indikator'],
            (string) ($r['baseline_2023'] ?? ''),
            (string) ($r['baseline_2024'] ?? ''),
            (string) ($r['keterangan_renstra'] ?? ''),
            (string) ($r['pengukuran'] ?? ''),
            (string) ($r['target_universitas'] ?? ''),
            (string) ($r['target_renop_prodi'] ?? ''),
            (string) ($r['target_akhir'] ?? ''),
            (string) ($r['catatan_perhitungan'] ?? ''),
            (string) ($r['realisasi'] ?? ''),
            (string) ($r['link_bukti'] ?? ''),
            (string) ($r['konfirmasi_auditor'] ?? ''),
            (string) ($r['catatan_auditor'] ?? ''),
            (string) ($r['status'] ?? ''),
            $r['capaian'] !== null ? round((float) $r['capaian'], 4) : '',
        ];

        $pendRows = array_values(array_map(
            $formatIndicatorRow,
            array_filter($indicators, static fn (array $i): bool => $i['standar_nomor'] >= 1 && $i['standar_nomor'] <= 8)
        ));
        $penelRows = array_values(array_map(
            $formatIndicatorRow,
            array_filter($indicators, static fn (array $i): bool => $i['standar_nomor'] >= 9 && $i['standar_nomor'] <= 16)
        ));
        $pkmRows = array_values(array_map(
            $formatIndicatorRow,
            array_filter($indicators, static fn (array $i): bool => $i['standar_nomor'] >= 17 && $i['standar_nomor'] <= 24)
        ));
        $tambahanRows = array_values(array_map(
            $formatIndicatorRow,
            array_filter($indicators, static fn (array $i): bool => $i['standar_nomor'] >= 25)
        ));
        $allIndicatorRows = array_values(array_map($formatIndicatorRow, $indicators));

        $summaryRows = array_map(static fn (array $s): array => [
            (int) $s['urutan'],
            "Std {$s['standar_nomor']} - {$s['standar_nama']}",
            (string) $s['kelompok_standar'],
            (int) $s['jumlah_indikator'],
            (int) $s['jumlah_tercapai'],
            (int) $s['jumlah_tidak_tercapai'],
            round((float) $s['persentase_tercapai'], 2),
            round((float) $s['rata_rata_capaian'], 2),
        ], $summaries);

        $benchmarkCols = array_merge(
            ['Standar Mutu', 'Sub Kelompok', 'No', 'Indikator Mutu'],
            self::BENCHMARK_PRODI_LIST,
            ['Rekap UNUSA', 'Baseline 2023', 'Target 2024', 'Asal Data 2023', 'Asal Data 2024', 'Catatan Review']
        );

        $benchmarkRows = array_map(static function (array $b): array {
            $pv = $b['prodi_values'] ?? [];
            $prodiCells = [];
            foreach (self::BENCHMARK_PRODI_LIST as $prodiName) {
                $prodiCells[] = (string) ($pv[$prodiName] ?? '');
            }

            return array_merge(
                [
                    "Std {$b['standar_nomor']}: {$b['standar_nama']}",
                    (string) ($b['sub_kelompok'] ?? ''),
                    (string) ($b['no_butir'] ?? ''),
                    (string) $b['indikator'],
                ],
                $prodiCells,
                [
                    (string) ($b['rekap_unusa'] ?? ''),
                    (string) ($b['baseline_2023'] ?? ''),
                    (string) ($b['target_2024'] ?? ''),
                    (string) ($b['asal_data_2023'] ?? ''),
                    (string) ($b['asal_data_2024'] ?? ''),
                    (string) ($b['catatan_review_prodi'] ?? ''),
                ]
            );
        }, $benchmarks);

        $ptkCols = [
            'Jenis PTK',
            'Tahun Akademik',
            'No',
            'Kriteria / Standar',
            'Tanggal',
            'Uraian Temuan',
            'Kategori',
            'Analisis Penyebab',
            'Tindakan Koreksi / Pencegahan',
            'Rencana Selesai',
            'Realisasi',
            'Status',
            'Link Bukti Dokumen',
            'Konfirmasi Auditor',
        ];

        $jenisLabel = static fn (string $jenis): string => match ($jenis) {
            'borang_lam' => 'PTK Borang LAM',
            'standar_28' => 'PTK 28 Standar',
            'bank_dokumen' => 'PTK Bank Dokumen',
            default => $jenis,
        };

        $ptkRows = array_map(static fn (array $p): array => [
            $jenisLabel((string) $p['jenis_ptk']),
            (string) $p['tahun_akademik'],
            (string) ($p['no_temuan'] ?? ''),
            (string) ($p['kriteria_atau_standar'] ?? ''),
            (string) ($p['tanggal_temuan'] ?? ''),
            (string) $p['uraian'],
            (string) ($p['kategori'] ?? ''),
            (string) ($p['analisis_penyebab'] ?? ''),
            (string) ($p['tindakan_koreksi'] ?? ''),
            (string) ($p['rencana_selesai'] ?? ''),
            (string) ($p['realisasi_selesai'] ?? ''),
            (string) ($p['status'] ?? ''),
            (string) ($p['link_bukti'] ?? ''),
            (string) ($p['konfirmasi_auditor'] ?? ''),
        ], $ptkFindings);

        $ptkByYear = [];
        foreach ($ptkFindings as $p) {
            $yr = (string) $p['tahun_akademik'];
            if (! isset($ptkByYear[$yr])) {
                $ptkByYear[$yr] = [
                    'total' => 0,
                    'selesai' => 0,
                    'reschedule' => 0,
                    'mayor' => 0,
                    'minor' => 0,
                ];
            }
            $ptkByYear[$yr]['total']++;
            $st = strtolower((string) ($p['status'] ?? ''));
            if ($st === 'selesai') {
                $ptkByYear[$yr]['selesai']++;
            } elseif ($st === 'reschedule') {
                $ptkByYear[$yr]['reschedule']++;
            }
            $kat = strtolower((string) ($p['kategori'] ?? ''));
            if (str_contains($kat, 'mayor')) {
                $ptkByYear[$yr]['mayor']++;
            }
            if (str_contains($kat, 'minor')) {
                $ptkByYear[$yr]['minor']++;
            }
        }
        ksort($ptkByYear);

        $ptkSummaryRows = [];
        foreach ($ptkByYear as $yearLabel => $stats) {
            $pct = $stats['total'] > 0 ? round(($stats['selesai'] / $stats['total']) * 100, 2) : 0.0;
            $ptkSummaryRows[] = [
                $yearLabel,
                $stats['total'],
                $stats['selesai'],
                $stats['reschedule'],
                $stats['mayor'],
                $stats['minor'],
                $pct,
            ];
        }

        $saranRows = array_map(static fn (array $r): array => [
            (int) $r['no_urut'],
            (string) $r['aspek_bidang'],
            (string) $r['kelebihan'],
            (string) $r['peluang_peningkatan'],
            (string) ($r['tanggal_audit'] ?? 'Surabaya, 21 September 2026'),
        ], $recommendations);

        $datasetSpecs = [
            [
                'code' => 'DS-13',
                'source_form_id' => $formsMap['F-06']->id,
                'tahun' => '2025',
                'periode' => 'Ganjil',
                'format' => 'XLSX',
                'updated_label' => '21 Sep 2026',
                'title' => 'Rekap Capaian 28 Standar AMI SNPT-U S1 Sistem Informasi 2025/2026',
                'description' => 'Rekapitulasi persentase ketercapaian status dan rata-rata nilai capaian per standar pada Instrumen SNPT-U 28 Standar Prodi S1 Sistem Informasi.',
                'cols' => ['Urutan', 'Standar Mutu', 'Kelompok Standar', 'Jumlah Indikator', 'Tercapai', 'Tidak Tercapai', 'Status Tercapai (%)', 'Rata-rata Capaian (%)'],
                'rows' => $summaryRows,
            ],
            [
                'code' => 'DS-14',
                'source_form_id' => $formsMap['F-06']->id,
                'tahun' => '2025',
                'periode' => 'Ganjil',
                'format' => 'XLSX',
                'updated_label' => '21 Sep 2026',
                'title' => 'Instrumen AMI 28 Standar S1 SI - Standar Pendidikan (Std 1–8)',
                'description' => 'Data lengkap indikator AMI kelompok Standar Pendidikan (Standar 1 s.d. 8) beserta baseline 2023–2024, target, realisasi, tautan bukti, dan konfirmasi auditor.',
                'cols' => $indicatorCols,
                'rows' => $pendRows,
            ],
            [
                'code' => 'DS-15',
                'source_form_id' => $formsMap['F-06']->id,
                'tahun' => '2025',
                'periode' => 'Ganjil',
                'format' => 'XLSX',
                'updated_label' => '21 Sep 2026',
                'title' => 'Instrumen AMI 28 Standar S1 SI - Standar Penelitian (Std 9–16)',
                'description' => 'Data lengkap indikator AMI kelompok Standar Penelitian (Standar 9 s.d. 16) Prodi S1 Sistem Informasi 2025/2026.',
                'cols' => $indicatorCols,
                'rows' => $penelRows,
            ],
            [
                'code' => 'DS-16',
                'source_form_id' => $formsMap['F-06']->id,
                'tahun' => '2025',
                'periode' => 'Ganjil',
                'format' => 'XLSX',
                'updated_label' => '21 Sep 2026',
                'title' => 'Instrumen AMI 28 Standar S1 SI - Standar PkM (Std 17–24)',
                'description' => 'Data lengkap indikator AMI kelompok Standar Pengabdian kepada Masyarakat (Standar 17 s.d. 24) Prodi S1 Sistem Informasi 2025/2026.',
                'cols' => $indicatorCols,
                'rows' => $pkmRows,
            ],
            [
                'code' => 'DS-17',
                'source_form_id' => $formsMap['F-06']->id,
                'tahun' => '2025',
                'periode' => 'Ganjil',
                'format' => 'XLSX',
                'updated_label' => '21 Sep 2026',
                'title' => 'Instrumen AMI 28 Standar S1 SI - Standar Tambahan Institusi (Std 25–38)',
                'description' => 'Data lengkap indikator AMI kelompok Standar Tambahan UNUSA (Standar 25 s.d. 38) Prodi S1 Sistem Informasi 2025/2026.',
                'cols' => $indicatorCols,
                'rows' => $tambahanRows,
            ],
            [
                'code' => 'DS-18',
                'source_form_id' => $formsMap['F-06']->id,
                'tahun' => '2025',
                'periode' => 'Ganjil',
                'format' => 'XLSX',
                'updated_label' => '21 Sep 2026',
                'title' => 'Instrumen AMI 28 Standar S1 Sistem Informasi 2025/2026 (Lengkap)',
                'description' => 'Gabungan utuh seluruh indikator Instrumen SNPT-U 28 Standar (Permendiktisaintek No. 39 Th 2025) Prodi S1 Sistem Informasi.',
                'cols' => $indicatorCols,
                'rows' => $allIndicatorRows,
            ],
            [
                'code' => 'DS-19',
                'source_form_id' => $formsMap['F-06']->id,
                'tahun' => '2024',
                'periode' => 'Genap',
                'format' => 'XLSX',
                'updated_label' => '15 Agu 2024',
                'title' => 'Benchmark 38 Standar SNPT-U Lintas 16 Prodi UNUSA (Baseline 2023 & Target 2024)',
                'description' => 'Matriks perbandingan capaian 38 Standar SNPT-U lintas 16 program studi di UNUSA termasuk S1 Sistem Informasi, rekap universitas, dan target 2024.',
                'cols' => $benchmarkCols,
                'rows' => $benchmarkRows,
            ],
            [
                'code' => 'DS-20',
                'source_form_id' => $formsMap['F-07']->id,
                'tahun' => '2025',
                'periode' => 'Ganjil',
                'format' => 'XLSX',
                'updated_label' => '21 Sep 2026',
                'title' => 'Permintaan Tindakan Koreksi (PTK) AMI S1 Sistem Informasi (2018–2026)',
                'description' => 'Seluruh riwayat temuan Audit Mutu Internal Prodi S1 Sistem Informasi dari formulir PTK Borang LAM, PTK 28 Standar, dan PTK Bank Dokumen beserta tindak lanjut.',
                'cols' => $ptkCols,
                'rows' => $ptkRows,
            ],
            [
                'code' => 'DS-21',
                'source_form_id' => $formsMap['F-07']->id,
                'tahun' => '2025',
                'periode' => 'Ganjil',
                'format' => 'CSV',
                'updated_label' => '21 Sep 2026',
                'title' => 'Rekap Temuan PTK AMI S1 Sistem Informasi per Tahun Akademik (2018–2026)',
                'description' => 'Ringkasan statistik temuan PTK AMI berdasarkan tahun akademik, kategori (Mayor/Minor), status penyelesaian (Selesai/Reschedule), dan persentase ketercapaian.',
                'cols' => ['Tahun Akademik', 'Total Temuan', 'Selesai', 'Reschedule', 'Mayor', 'Minor', 'Ketercapaian (%)'],
                'rows' => $ptkSummaryRows,
            ],
            [
                'code' => 'DS-22',
                'source_form_id' => $formsMap['F-08']->id,
                'tahun' => '2026',
                'periode' => 'Ganjil',
                'format' => 'CSV',
                'updated_label' => '21 Sep 2026',
                'title' => 'Saran Pengembangan Audit Mutu Internal S1 Sistem Informasi 2026',
                'description' => 'Rekap rekomendasi kelebihan dan peluang peningkatan hasil Audit Mutu Internal Prodi S1 Sistem Informasi UNUSA tanggal 21 September 2026.',
                'cols' => ['No', 'Aspek / Bidang', 'Kelebihan', 'Peluang untuk Peningkatan', 'Tanggal Audit'],
                'rows' => $saranRows,
            ],
        ];

        $saved = [];
        foreach ($datasetSpecs as $spec) {
            $saved[$spec['code']] = Dataset::query()->updateOrCreate(
                ['code' => $spec['code']],
                [
                    'id_kategori' => $amiCategory->id,
                    'id_formulir_sumber' => $spec['source_form_id'],
                    'judul' => $spec['title'],
                    'deskripsi' => $spec['description'],
                    'tahun' => (string) $spec['tahun'],
                    'periode' => $spec['periode'],
                    'tipe_akses' => 'public',
                    'format' => $spec['format'],
                    'label_diperbarui' => $spec['updated_label'],
                    'kolom' => $spec['cols'],
                    'baris' => $spec['rows'],
                    'dibuat_oleh' => $creatorId,
                ]
            );
        }

        return $saved;
    }

    /**
     * Upsert featured Dashboard visualizations derived from AMI datasets.
     *
     * @param  array<string, Dataset>  $datasets
     */
    protected function upsertAmiVisualizations(array $datasets, ?int $creatorId): int
    {
        $specs = [
            [
                'code' => 'V-04',
                'title' => 'Capaian 28 Standar AMI SNPT-U S1 SI 2025/2026 (%)',
                'dataset_code' => 'DS-13',
                'label_col' => 1,
                'value_col' => 6,
                'kind' => 'hbar',
                'color' => 'indigo',
                'show' => true,
            ],
            [
                'code' => 'V-05',
                'title' => 'Rekap Temuan PTK AMI per Tahun Akademik',
                'dataset_code' => 'DS-21',
                'label_col' => 0,
                'value_col' => 1,
                'kind' => 'bar',
                'color' => 'cyan',
                'show' => true,
            ],
        ];

        $count = 0;
        foreach ($specs as $spec) {
            $ds = $datasets[$spec['dataset_code']] ?? null;
            if (! $ds) {
                continue;
            }
            Visualization::query()->updateOrCreate(
                ['code' => $spec['code']],
                [
                    'judul' => $spec['title'],
                    'id_dataset' => $ds->id,
                    'tipe_grafik' => $spec['kind'],
                    'konfigurasi_query' => [
                        'mode' => 'dataset',
                        'label_col' => $spec['label_col'],
                        'value_col' => $spec['value_col'],
                        'color' => $spec['color'],
                        'show' => $spec['show'],
                    ],
                    'dibuat_oleh' => $creatorId,
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Parse Sheet 49 (`8 Pendidikan + 3 Penelitian + 3`).
     *
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function parseMainIndicators(array $rows): array
    {
        $kelompok = 'Std Luaran Pendidikan';
        $standarNomor = 0;
        $standarNama = '';
        $list = [];
        $seqByStandard = [];

        foreach ($rows as $rowNum => $cells) {
            if ($rowNum < 6) {
                continue;
            }

            $colA = $this->cleanText($cells[0] ?? '');
            $colB = $this->cleanText($cells[1] ?? '');
            $colC = $this->cleanText($cells[2] ?? '');
            $colD = $this->cleanText($cells[3] ?? '');
            $colE = $this->cleanText($cells[4] ?? '');
            $colH = $this->cleanText($cells[7] ?? '');
            $colI = $this->cleanText($cells[8] ?? '');
            $colJ = $this->cleanText($cells[9] ?? '');
            $colK = $this->cleanText($cells[10] ?? '');
            $colL = $this->cleanText($cells[11] ?? '');
            $colM = $this->cleanText($cells[12] ?? '');
            $colN = $this->cleanText($cells[13] ?? '');
            $colO = $this->cleanText($cells[14] ?? '');
            $colP = $this->cleanText($cells[15] ?? '');
            $colQ = $this->cleanText($cells[16] ?? '');
            $colR = $this->cleanText($cells[17] ?? '');
            $colS = $this->cleanText($cells[18] ?? '');

            if (preg_match('/^Std\s+/i', $colA) === 1) {
                $kelompok = $colA;
            }

            if (preg_match('/^Standar\s+(\d+)\s*:\s*(.+)$/is', $colB, $m) === 1) {
                $standarNomor = (int) $m[1];
                $standarNama = trim((string) preg_replace('/\s+/', ' ', $m[2]));

                if ($standarNomor === 2) {
                    $kelompok = 'Std Masukan Pendidikan';
                } elseif ($standarNomor >= 25) {
                    $kelompok = 'Standar Tambahan';
                }
            }

            if ($standarNomor === 0) {
                continue;
            }

            if ($colC !== '' && stripos($colC, 'Kriteria mitra') !== false) {
                continue;
            }

            $hasAnyPayload = ($colD !== '' || $colE !== '' || $colN !== '' || $colR !== '' || $colJ !== '' || $colL !== '');
            if (! $hasAnyPayload) {
                continue;
            }

            $indikatorText = $colD;
            $noButir = $this->normalizeNumberString($colC);

            if ($indikatorText === '') {
                if (! empty($list) && $list[array_key_last($list)]['standar_nomor'] === $standarNomor) {
                    $prev = $list[array_key_last($list)];
                    $suffix = $colH !== '' ? "({$colH} - Lanjutan)" : '(Lanjutan)';
                    $indikatorText = "{$prev['indikator']} {$suffix}";
                    $noButir = $prev['no_butir'];
                } else {
                    continue;
                }
            }

            $seqByStandard[$standarNomor] = ($seqByStandard[$standarNomor] ?? 0) + 1;
            $seq = $seqByStandard[$standarNomor];
            $code = sprintf('AMI-S%02d-%02d', $standarNomor, $seq);

            $realisasiClean = $this->normalizeMetricCell($colN);
            if (is_numeric($realisasiClean) && (float) $realisasiClean > 40000 && (float) $realisasiClean < 50000 && $standarNomor === 21) {
                $realisasiClean = $this->formatExcelDateOrRaw($realisasiClean) ?? $realisasiClean;
            }

            $capaianNum = $this->parseNullableFloat($colS);

            $list[] = [
                'code' => $code,
                'kelompok_standar' => $kelompok,
                'standar_nomor' => $standarNomor,
                'standar_nama' => $standarNama,
                'no_butir' => $noButir !== '' ? $noButir : null,
                'indikator' => $indikatorText,
                'baseline_2023' => $this->nullableMetric($colE),
                'baseline_2024' => $this->nullableMetric($cells[5] ?? ''),
                'keterangan_umum' => $this->nullableText($cells[6] ?? ''),
                'keterangan_renstra' => $this->nullableText($colH),
                'pengukuran' => $this->nullableText($colI),
                'target_universitas' => $this->nullableMetric($colJ),
                'target_renop_prodi' => $this->nullableMetric($colK),
                'target_akhir' => $this->nullableMetric($colL),
                'catatan_perhitungan' => $this->nullableText($colM),
                'realisasi' => $realisasiClean !== '' ? $realisasiClean : null,
                'link_bukti' => $this->nullableText($colO),
                'konfirmasi_auditor' => $colP !== '' ? strtoupper($colP) : null,
                'catatan_auditor' => $this->nullableText($colQ),
                'status' => $colR !== '' ? $colR : null,
                'capaian' => $capaianNum,
                'sort_order' => count($list) + 1,
            ];
        }

        return $list;
    }

    /**
     * Aggregate per-standard attainment summaries preserving workbook appearance order.
     *
     * @param  array<int, array<string, mixed>>  $indicators
     * @return array<int, array<string, mixed>>
     */
    protected function buildStandardSummaries(array $indicators): array
    {
        $grouped = [];
        foreach ($indicators as $item) {
            $stdNum = (int) $item['standar_nomor'];
            if (! isset($grouped[$stdNum])) {
                $grouped[$stdNum] = [
                    'urutan' => count($grouped) + 1,
                    'standar_nomor' => $stdNum,
                    'standar_nama' => (string) $item['standar_nama'],
                    'kelompok_standar' => (string) $item['kelompok_standar'],
                    'jumlah_indikator' => 0,
                    'jumlah_tercapai' => 0,
                    'jumlah_tidak_tercapai' => 0,
                    'capaian_sum' => 0.0,
                ];
            }

            $grouped[$stdNum]['jumlah_indikator']++;
            $status = strtolower((string) ($item['status'] ?? ''));
            if ($status === 'tercapai') {
                $grouped[$stdNum]['jumlah_tercapai']++;
            } else {
                $grouped[$stdNum]['jumlah_tidak_tercapai']++;
            }

            if ($item['capaian'] !== null) {
                $grouped[$stdNum]['capaian_sum'] += (float) $item['capaian'];
            }
        }

        $summaries = [];
        foreach ($grouped as $g) {
            $total = max(1, $g['jumlah_indikator']);
            $summaries[] = [
                'urutan' => $g['urutan'],
                'standar_nomor' => $g['standar_nomor'],
                'standar_nama' => $g['standar_nama'],
                'kelompok_standar' => $g['kelompok_standar'],
                'jumlah_indikator' => $g['jumlah_indikator'],
                'jumlah_tercapai' => $g['jumlah_tercapai'],
                'jumlah_tidak_tercapai' => $g['jumlah_tidak_tercapai'],
                'persentase_tercapai' => round(($g['jumlah_tercapai'] / $total) * 100, 4),
                'rata_rata_capaian' => round(($g['capaian_sum'] / $total) * 100, 4),
            ];
        }

        return $summaries;
    }

    /**
     * Extract Prodi discussion notes from Sheet 45 (`After review Prodi`) keyed by "std|indicator".
     *
     * @param  array<int, array<int, string>>  $rows
     * @return array<string, string>
     */
    protected function extractReviewNotes(array $rows): array
    {
        $notes = [];
        $standarNomor = 0;

        foreach ($rows as $rowNum => $cells) {
            if ($rowNum < 6) {
                continue;
            }
            $colA = $this->cleanText($cells[0] ?? '');
            $colB = $this->cleanText($cells[1] ?? '');

            if (preg_match('/^Standar\s+(\d+)\s*:/i', $colA, $m) === 1) {
                $standarNomor = (int) $m[1];
                continue;
            }

            if ($standarNomor === 0 || $colB === '') {
                continue;
            }

            $parts = [];
            foreach ([23, 24, 25] as $idx) {
                $v = $this->cleanText($cells[$idx] ?? '');
                if ($v !== '') {
                    $parts[] = $v;
                }
            }

            if (! empty($parts)) {
                $key = $standarNomor.'|'.mb_strtolower(preg_replace('/\s+/', ' ', $colB) ?? $colB);
                $notes[$key] = implode(' | ', $parts);
            }
        }

        return $notes;
    }

    /**
     * Parse Sheet 43 (`Target 24`) multi-prodi UNUSA benchmarks.
     *
     * @param  array<int, array<int, string>>  $rows
     * @param  array<string, string>  $reviewNotesMap
     * @return array<int, array<string, mixed>>
     */
    protected function parseBenchmarks(array $rows, array $reviewNotesMap): array
    {
        $standarNomor = 0;
        $standarNama = '';
        $subKelompok = null;
        $list = [];

        foreach ($rows as $rowNum => $cells) {
            if ($rowNum < 6) {
                continue;
            }

            $colA = $this->cleanText($cells[0] ?? '');
            $colB = $this->cleanText($cells[1] ?? '');

            if (preg_match('/^Standar\s+(\d+)\s*:\s*(.+)$/is', $colA, $m) === 1) {
                $standarNomor = (int) $m[1];
                $standarNama = trim((string) preg_replace('/\s+/', ' ', $m[2]));
                $subKelompok = null;
                continue;
            }

            if ($standarNomor === 0) {
                continue;
            }

            if (in_array(strtoupper($colA), ['SNPT', 'IKU SNDIKTI', 'IKT UNUSA', 'MBKM'], true)) {
                $subKelompok = $colA;
                continue;
            }

            if ($colA !== '' && stripos($colA, 'Kriteria mitra') !== false) {
                continue;
            }

            if ($colA === 'No.' && stripos($colB, 'Indikator') !== false) {
                continue;
            }

            $rekap = $this->normalizeMetricCell($cells[18] ?? '');
            $hasProdiVal = false;
            for ($c = 2; $c <= 17; $c++) {
                if (trim((string) ($cells[$c] ?? '')) !== '') {
                    $hasProdiVal = true;
                    break;
                }
            }

            if ($colB === '' && ! $hasProdiVal && $rekap === '') {
                continue;
            }

            $indikatorText = $colB;
            $noButir = $this->normalizeNumberString($colA);

            if ($indikatorText === '' && ! empty($list) && $list[array_key_last($list)]['standar_nomor'] === $standarNomor) {
                $prev = $list[array_key_last($list)];
                $indikatorText = "{$prev['indikator']} (Lanjutan)";
                $noButir = $prev['no_butir'];
            }

            if ($indikatorText === '') {
                continue;
            }

            $prodiValues = [];
            foreach (self::BENCHMARK_PRODI_LIST as $offset => $prodiName) {
                $rawCell = $this->normalizeMetricCell($cells[2 + $offset] ?? '');
                $prodiValues[$prodiName] = $rawCell;
            }

            $lookupKey = $standarNomor.'|'.mb_strtolower(preg_replace('/\s+/', ' ', $indikatorText) ?? $indikatorText);
            $note = $reviewNotesMap[$lookupKey] ?? null;

            $list[] = [
                'standar_nomor' => $standarNomor,
                'standar_nama' => $standarNama,
                'sub_kelompok' => $subKelompok,
                'no_butir' => $noButir !== '' ? $noButir : null,
                'indikator' => $indikatorText,
                'prodi_values' => $prodiValues,
                'nilai_si' => $prodiValues['S1 Sistem Informasi'] !== '' ? $prodiValues['S1 Sistem Informasi'] : null,
                'rekap_unusa' => $rekap !== '' ? $rekap : null,
                'baseline_2023' => $this->nullableMetric($cells[19] ?? ''),
                'target_2024' => $this->nullableMetric($cells[20] ?? ''),
                'asal_data_2023' => $this->nullableText($cells[21] ?? ''),
                'asal_data_2024' => $this->nullableText($cells[22] ?? ''),
                'catatan_review_prodi' => $note,
                'sort_order' => count($list) + 1,
            ];
        }

        return $list;
    }

    /**
     * Parse Sheet 50 (`PTK Borang LAM`) and Sheet 51 (`PTK Platform 28 Standar`).
     *
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function parsePtkStandardSheet(
        array $rows,
        string $jenisPtk,
        string $defaultYear,
        int $orderOffset = 0
    ): array {
        $currentYear = $defaultYear;
        $list = [];

        foreach ($rows as $rowNum => $cells) {
            if ($rowNum < 13) {
                continue;
            }

            $colA = $this->cleanText($cells[0] ?? '');
            $colB = $this->cleanText($cells[1] ?? '');
            $colC = $this->cleanText($cells[2] ?? '');
            $colD = $this->cleanText($cells[3] ?? '');
            $colE = $this->cleanText($cells[4] ?? '');
            $colF = $this->cleanText($cells[5] ?? '');
            $colG = $this->cleanText($cells[6] ?? '');
            $colH = $this->cleanText($cells[7] ?? '');
            $colI = $this->cleanText($cells[8] ?? '');
            $colJ = $this->cleanText($cells[9] ?? '');
            $colK = $this->cleanText($cells[10] ?? '');
            $colL = $this->cleanText($cells[11] ?? '');
            $colM = $this->cleanText($cells[12] ?? '');

            if (
                stripos($colD, 'Ketua KPMPI') !== false
                || stripos($colH, 'Surabaya,') !== false
                || stripos($colF, 'Surabaya,') !== false
            ) {
                break;
            }

            if (preg_match('/^PTK\s+Tahun\s+(.+)$/i', $colA, $ym) === 1) {
                $currentYear = str_replace('/', '-', trim($ym[1]));
                continue;
            }

            if (
                strcasecmp($colI, 'Tingkat Ketercapaian') === 0
                || strcasecmp($colA, 'No') === 0
                || in_array(strtoupper($colB), ['KRITERIA', 'STANDAR'], true)
            ) {
                continue;
            }

            if ($colD === '') {
                continue;
            }

            $tanggal = $this->formatExcelDateOrRaw($colC);
            if ($tanggal === '21/09/1026') {
                $tanggal = '2026-09-21';
            }

            $rencana = $this->formatExcelDateOrRaw($colH);
            $realisasi = $this->normalizeNumberString($colI);

            $statusClean = null;
            if ($colJ !== '') {
                $lower = mb_strtolower($colJ);
                $statusClean = str_contains($lower, 'selesai') ? 'Selesai' : (str_contains($lower, 'reschedule') ? 'Reschedule' : $colJ);
            }

            $list[] = [
                'jenis_ptk' => $jenisPtk,
                'tahun_akademik' => $currentYear,
                'no_temuan' => $this->normalizeNumberString($colA) ?: null,
                'kriteria_atau_standar' => $colB !== '' ? $this->normalizeNumberString($colB) : null,
                'tanggal_temuan' => $tanggal,
                'uraian' => $colD,
                'kategori' => $this->normalizePtkCategory($colE),
                'analisis_penyebab' => $colF !== '' ? $colF : null,
                'tindakan_koreksi' => $colG !== '' ? $colG : null,
                'rencana_selesai' => $rencana,
                'realisasi_selesai' => $realisasi !== '' ? $realisasi : null,
                'status' => $statusClean,
                'link_bukti' => $colK !== '' ? $colK : null,
                'konfirmasi_auditor' => $colL !== '' ? $colL : null,
                'catatan' => $colM !== '' ? $colM : null,
                'sort_order' => $orderOffset + count($list) + 1,
            ];
        }

        return $list;
    }

    /**
     * Parse Sheet 52 (`PTK Bank Dokumen`) which stores findings in Columns A, B, G, H, I.
     *
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function parsePtkBankDokumen(array $rows, int $orderOffset = 0): array
    {
        $currentYear = '2023-2024';
        $list = [];

        foreach ($rows as $rowNum => $cells) {
            if ($rowNum < 14) {
                continue;
            }

            $colA = $this->cleanText($cells[0] ?? '');
            $colB = $this->cleanText($cells[1] ?? '');
            $colF = $this->cleanText($cells[5] ?? '');
            $colG = $this->cleanText($cells[6] ?? '');
            $colH = $this->cleanText($cells[7] ?? '');
            $colI = $this->cleanText($cells[8] ?? '');

            if (stripos($colB, 'Ketua KPMPI') !== false || stripos($colF, 'Surabaya,') !== false) {
                break;
            }

            if (preg_match('/^(20\d{2}\s*[-–\/]\s*20\d{2})$/', $colA, $ym) === 1) {
                $currentYear = preg_replace('/\s+/', '', str_replace('/', '-', $ym[1])) ?? $colA;
                continue;
            }

            if (strcasecmp($colA, 'Update Dokumen') === 0 || strcasecmp($colF, 'Tingkat Ketercapaian') === 0) {
                continue;
            }

            if ($colB === '') {
                continue;
            }

            $statusClean = null;
            if ($colG !== '') {
                $lower = mb_strtolower($colG);
                $statusClean = str_contains($lower, 'selesai') ? 'Selesai' : (str_contains($lower, 'reschedule') ? 'Reschedule' : $colG);
            }

            $list[] = [
                'jenis_ptk' => 'bank_dokumen',
                'tahun_akademik' => $currentYear,
                'no_temuan' => $this->normalizeNumberString($colA) ?: null,
                'kriteria_atau_standar' => 'Bank Dokumen Prodi',
                'tanggal_temuan' => null,
                'uraian' => $colB,
                'kategori' => 'Dokumen',
                'analisis_penyebab' => null,
                'tindakan_koreksi' => null,
                'rencana_selesai' => null,
                'realisasi_selesai' => null,
                'status' => $statusClean,
                'link_bukti' => $colH !== '' ? $colH : null,
                'konfirmasi_auditor' => $colI !== '' ? $colI : null,
                'catatan' => null,
                'sort_order' => $orderOffset + count($list) + 1,
            ];
        }

        return $list;
    }

    /**
     * Parse Sheet 53 (`Saran Pengembangan`).
     *
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function parseRecommendations(array $rows): array
    {
        $tanggalAudit = 'Surabaya, 21 September 2026';
        $ketuaSpmi = 'Nur Masruroh, SST., M.Keb.';
        $wakilAuditee = 'Dr. Riyan Sisiawan Putra, S.E., M.SM., CPLM., CHRMP.';
        $auditorKetua = null;

        foreach ($rows as $rowNum => $cells) {
            if ($rowNum < 25) {
                continue;
            }
            $colA = $this->cleanText($cells[0] ?? '');
            $colE = $this->cleanText($cells[4] ?? '');
            if (stripos($colE, 'Surabaya,') !== false) {
                $tanggalAudit = $colE;
            }
            if (stripos($colA, 'Nur Masruroh') !== false) {
                $ketuaSpmi = $colA;
            }
            if (stripos($colE, 'Riyan Sisiawan') !== false) {
                $wakilAuditee = $colE;
            }
        }

        $list = [];
        $lastAspect = 'Proses pembelajaran';

        foreach ($rows as $rowNum => $cells) {
            if ($rowNum < 6 || $rowNum > 30) {
                continue;
            }

            $noStr = $this->normalizeNumberString($this->cleanText($cells[0] ?? ''));
            $aspect = $this->cleanText($cells[1] ?? '');
            $kelebihan = $this->cleanText($cells[2] ?? '');
            $peluang = $this->cleanText($cells[3] ?? '');

            if ($noStr === '' || ! is_numeric($noStr) || ($kelebihan === '' && $peluang === '')) {
                continue;
            }

            if ($aspect !== '') {
                $lastAspect = $aspect;
            } else {
                $aspect = $lastAspect;
            }

            $list[] = [
                'no_urut' => (int) $noStr,
                'aspek_bidang' => $aspect,
                'kelebihan' => $kelebihan,
                'peluang_peningkatan' => $peluang,
                'tanggal_audit' => $tanggalAudit,
                'ketua_spmi' => $ketuaSpmi,
                'wakil_auditee' => $wakilAuditee,
                'auditor_ketua' => $auditorKetua,
            ];
        }

        return $list;
    }

    /**
     * Build interactive DataForm fields matching Sheet 54 (`Daftar Tilik`).
     *
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function buildChecklistFormFields(array $rows): array
    {
        return [
            ['id' => 'q1', 'label' => 'Jenis Instrumen', 'type' => 'select', 'required' => true, 'options' => ['SNPT-U 28 Standar', 'SNPT-U 38 Standar', 'Borang 9 Kriteria LAM']],
            ['id' => 'q2', 'label' => 'Sheet / Standar Mutu', 'type' => 'text', 'required' => true],
            ['id' => 'q3', 'label' => 'No Indikator', 'type' => 'text', 'required' => true],
            ['id' => 'q4', 'label' => 'Pertanyaan dari Auditor', 'type' => 'textarea', 'required' => true],
            ['id' => 'q5', 'label' => 'Hasil Observasi / Audit Visitasi', 'type' => 'textarea', 'required' => true],
            ['id' => 'q6', 'label' => 'Konfirmasi Auditor (S/TS)', 'type' => 'select', 'required' => true, 'options' => ['S (Sesuai)', 'TS (Tidak Sesuai)']],
            ['id' => 'q7', 'label' => 'Catatan Khusus', 'type' => 'textarea', 'required' => false],
            ['id' => 'q8', 'label' => 'Saran Pengembangan', 'type' => 'textarea', 'required' => false],
            ['id' => 'q9', 'label' => 'Kesimpulan', 'type' => 'textarea', 'required' => false],
            ['id' => 'q10', 'label' => 'Lampiran Berita Acara / Bukti Visitasi', 'type' => 'file', 'required' => false],
        ];
    }

    /**
     * Read all shared strings using DOMDocument so rich-text `<r><t>` elements concatenate cleanly.
     *
     * @return array<int, string>
     */
    protected function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $doc = new DOMDocument;
        if (! @$doc->loadXML($xml)) {
            return [];
        }

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $nodes = $xpath->query('//s:sst/s:si');

        $strings = [];
        if ($nodes !== false) {
            foreach ($nodes as $si) {
                $strings[] = $si->textContent;
            }
        }

        return $strings;
    }

    /**
     * Map worksheet display names to internal zip paths (e.g. `xl/worksheets/sheet49.xml`).
     *
     * @return array<string, string>
     */
    protected function readSheetTargets(ZipArchive $zip): array
    {
        $wbXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($wbXml === false || $relsXml === false) {
            return [];
        }

        $relsDoc = new DOMDocument;
        $relsDoc->loadXML($relsXml);
        $relsMap = [];
        foreach ($relsDoc->getElementsByTagName('Relationship') as $rel) {
            /** @var DOMElement $rel */
            $relsMap[$rel->getAttribute('Id')] = $rel->getAttribute('Target');
        }

        $wbDoc = new DOMDocument;
        $wbDoc->loadXML($wbXml);
        $targets = [];
        foreach ($wbDoc->getElementsByTagName('sheet') as $sheet) {
            /** @var DOMElement $sheet */
            $name = $sheet->getAttribute('name');
            $rid = $sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
            if (isset($relsMap[$rid])) {
                $target = ltrim($relsMap[$rid], '/');
                if (! str_starts_with($target, 'xl/')) {
                    $target = 'xl/'.$target;
                }
                $targets[$name] = $target;
            }
        }

        return $targets;
    }

    /**
     * Read all rows from a worksheet XML into sparse `[rowNumber => [colIndex => stringValue]]`.
     *
     * @param  array<int, string>  $sharedStrings
     * @return array<int, array<int, string>>
     */
    protected function readSheetRows(ZipArchive $zip, string $entryPath, array $sharedStrings): array
    {
        if ($entryPath === '') {
            return [];
        }

        $xml = $zip->getFromName($entryPath);
        if ($xml === false) {
            return [];
        }

        $doc = new DOMDocument;
        if (! @$doc->loadXML($xml)) {
            return [];
        }

        $rows = [];
        foreach ($doc->getElementsByTagName('row') as $rowEl) {
            /** @var DOMElement $rowEl */
            $rowNumber = (int) $rowEl->getAttribute('r');
            $rowCells = [];

            foreach ($rowEl->getElementsByTagName('c') as $cellEl) {
                /** @var DOMElement $cellEl */
                $coord = $cellEl->getAttribute('r');
                $colIdx = $this->columnIndexFromCoord($coord);
                $type = $cellEl->getAttribute('t');

                $value = '';
                if ($type === 's') {
                    $vNode = $cellEl->getElementsByTagName('v')->item(0);
                    if ($vNode !== null) {
                        $ssIdx = (int) $vNode->textContent;
                        $value = $sharedStrings[$ssIdx] ?? '';
                    }
                } elseif ($type === 'inlineStr') {
                    $isNode = $cellEl->getElementsByTagName('is')->item(0);
                    $value = $isNode !== null ? $isNode->textContent : '';
                } else {
                    $vNode = $cellEl->getElementsByTagName('v')->item(0);
                    $value = $vNode !== null ? $vNode->textContent : '';
                }

                if ($value !== '') {
                    $rowCells[$colIdx] = $value;
                }
            }

            if (! empty($rowCells)) {
                $rows[$rowNumber] = $rowCells;
            }
        }

        return $rows;
    }

    protected function columnIndexFromCoord(string $coord): int
    {
        $letters = preg_replace('/[^A-Z]/i', '', strtoupper($coord)) ?? '';
        $idx = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $idx = $idx * 26 + (ord($letters[$i]) - 64);
        }

        return max(0, $idx - 1);
    }

    protected function cleanText(string $value): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $value);

        return trim($normalized);
    }

    protected function nullableText(string $value): ?string
    {
        $clean = $this->cleanText($value);
        if ($clean === '' || $this->isExcelError($clean)) {
            return null;
        }

        return $clean;
    }

    protected function isExcelError(string $value): bool
    {
        return in_array(strtoupper(trim($value)), ['#REF!', '#DIV/0!', '#VALUE!', '#N/A', '#NAME?', '#NUM!', 'NAN'], true);
    }

    protected function normalizeNumberString(string $value): string
    {
        $trim = trim($value);
        if ($trim === '' || $this->isExcelError($trim)) {
            return '';
        }

        if (preg_match('/^\d+\.0+$/', $trim) === 1) {
            return (string) ((int) ((float) $trim));
        }

        return $trim;
    }

    protected function normalizeMetricCell(string $value): string
    {
        $clean = $this->cleanText($value);
        if ($clean === '' || $this->isExcelError($clean)) {
            return '';
        }

        if (preg_match('/^-?\d+\.0+$/', $clean) === 1) {
            return (string) ((int) ((float) $clean));
        }

        if (is_numeric($clean) && str_contains($clean, '.') && strlen($clean) > 10) {
            return rtrim(rtrim(number_format((float) $clean, 4, '.', ''), '0'), '.');
        }

        return $clean;
    }

    protected function nullableMetric(string $value): ?string
    {
        $norm = $this->normalizeMetricCell($value);

        return $norm !== '' ? $norm : null;
    }

    protected function parseNullableFloat(string $value): ?float
    {
        $trim = trim($value);
        if ($trim === '' || $this->isExcelError($trim) || ! is_numeric($trim)) {
            return null;
        }

        $num = (float) $trim;

        return is_finite($num) ? round($num, 4) : null;
    }

    protected function formatExcelDateOrRaw(string $value): ?string
    {
        $trim = $this->cleanText($value);
        if ($trim === '' || $this->isExcelError($trim)) {
            return null;
        }

        if (is_numeric($trim)) {
            $serial = (int) round((float) $trim);
            if ($serial <= 1) {
                return null;
            }
            if ($serial > 30000 && $serial < 60000) {
                $base = new DateTimeImmutable('1899-12-30');

                return $base->modify("+{$serial} days")->format('Y-m-d');
            }

            return $this->normalizeNumberString($trim);
        }

        return $trim;
    }

    protected function normalizePtkCategory(string $value): ?string
    {
        $clean = $this->cleanText($value);
        if ($clean === '') {
            return null;
        }

        return match (strtolower($clean)) {
            'minor' => 'Minor',
            'mayor' => 'Mayor',
            'mayor, dan minor', 'minor dan mayor' => 'Mayor dan Minor',
            default => $clean,
        };
    }
}
