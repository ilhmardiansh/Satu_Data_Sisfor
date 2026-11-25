<?php

namespace App\Services;

use App\Models\DataForm;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\SubmissionValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DataPreprocessingService
{
    public function __construct(
        protected DataQualityService $quality
    ) {}

    /**
     * Profile submission statuses and per-column completeness/validity metrics for pre-processing workspace.
     *
     * @param  Collection<int, FormSubmission>  $submissions
     * @param  array<int, array<int, string>>  $issuesMap
     * @return array<string, mixed>
     */
    public function profileForm(DataForm $form, Collection $submissions, array $issuesMap): array
    {
        $total = $submissions->count();
        $mentah = $submissions->where('status_validasi', 'mentah')->count();
        $valid = $submissions->where('status_validasi', 'valid')->count();
        $ditolak = $submissions->where('status_validasi', 'ditolak')->count();
        $activeSubs = $submissions->where('status_validasi', '!=', 'ditolak')->values();
        $activeCount = $activeSubs->count();

        $issueRows = 0;
        $duplicateRows = 0;
        foreach ($activeSubs as $sub) {
            $issues = $issuesMap[$sub->id] ?? [];
            if (! empty($issues)) {
                $issueRows++;
                if (collect($issues)->contains(fn (string $msg): bool => str_starts_with($msg, 'Data ganda'))) {
                    $duplicateRows++;
                }
            }
        }

        $formItems = $form->items ?? [];
        $activeFieldsBySub = [];
        foreach ($activeSubs as $sub) {
            $activeFieldsBySub[$sub->id] = $this->quality->resolveActiveFieldIds($formItems, $sub->values ?? []);
        }

        $columns = [];
        $missingCells = 0;
        $invalidCells = 0;
        $nonUniformCells = 0;
        $outlierCells = 0;

        foreach ($form->fields ?? [] as $field) {
            $fid = (string) ($field['id'] ?? '');
            $type = (string) ($field['type'] ?? 'text');
            $filled = 0;
            $missing = 0;
            $invalid = 0;
            $nonUniform = 0;
            $distinct = [];
            $numericSamples = [];

            foreach ($activeSubs as $sub) {
                $val = (string) ($sub->values[$fid] ?? '');
                $trimmed = trim($val);
                $isActiveForSub = isset($activeFieldsBySub[$sub->id][$fid]);

                if ($trimmed === '') {
                    if ($isActiveForSub) {
                        $missing++;
                    }
                } else {
                    $filled++;
                    $distinct[mb_strtolower($trimmed)] = true;
                }

                $fieldErr = ($isActiveForSub || $trimmed !== '') ? $this->quality->fieldError($field, $val) : null;
                if ($fieldErr !== null) {
                    $invalid++;
                } elseif (in_array($type, ['number', 'linear_scale', 'rating'], true) && $trimmed !== '') {
                    $parsedNum = ChartPaletteService::parseNumeric($trimmed);
                    if ($parsedNum !== null) {
                        $numericSamples[] = ['sub_code' => $sub->code, 'value' => $parsedNum];
                    }
                }

                if ($val !== '' && $this->quality->normalizeValue($field, $val) !== $val) {
                    $nonUniform++;
                }
            }

            $completeness = $activeCount > 0 ? round(($filled / $activeCount) * 100, 1) : 100.0;
            $uniqueCount = count($distinct);
            $numericStats = $this->computeNumericStats($numericSamples);

            $missingCells += $missing;
            $invalidCells += $invalid;
            $nonUniformCells += $nonUniform;
            $outlierCells += $numericStats['outlier_count'];

            $columns[] = [
                'id' => $fid,
                'label' => (string) ($field['label'] ?? $fid),
                'type' => $type,
                'required' => (bool) ($field['required'] ?? false),
                'filled' => $filled,
                'missing' => $missing,
                'completeness_pct' => $completeness,
                'invalid' => $invalid,
                'non_uniform' => $nonUniform,
                'unique_count' => $uniqueCount,
                'numeric_stats' => $numericStats,
                'viz_suitability' => $this->classifyVisualizationSuitability($type, $activeCount, $uniqueCount, $filled, $invalid),
            ];
        }

        $completenessAvg = ! empty($columns)
            ? round((float) collect($columns)->avg('completeness_pct'), 1)
            : 100.0;

        $standardStages = $this->buildStandardStages(
            $form,
            $activeCount,
            $valid,
            $issueRows,
            $duplicateRows,
            $missingCells,
            $invalidCells,
            $nonUniformCells
        );

        return [
            'total' => $total,
            'mentah' => $mentah,
            'valid' => $valid,
            'ditolak' => $ditolak,
            'active_count' => $activeCount,
            'issue_rows' => $issueRows,
            'duplicate_rows' => $duplicateRows,
            'clean_active_rows' => max(0, $activeCount - $issueRows),
            'missing_cells' => $missingCells,
            'invalid_cells' => $invalidCells,
            'non_uniform_cells' => $nonUniformCells,
            'outlier_cells' => $outlierCells,
            'completeness_avg' => $completenessAvg,
            'standard_stages' => $standardStages,
            'columns' => $columns,
        ];
    }

    /**
     * Execute safe automated steps of the Satu Data standard pipeline (Normalization -> Deduplication).
     *
     * @return array{
     *     changed_cells: int,
     *     changed_rows: int,
     *     dedup_handled: int,
     *     remaining_issues: int,
     *     active_count: int
     * }
     */
    public function runStandardPipeline(
        DataForm $form,
        int $actorId,
        string $textCase = 'none',
        string $scope = 'same_values'
    ): array {
        $normRes = $this->normalizeForm($form, $actorId, [
            'normalize_numbers' => true,
            'text_case' => $textCase,
        ]);

        $dedupRes = $this->deduplicateForm($form, $actorId, $scope, 'reject');

        $form->loadMissing('kolomFormulir');
        $allSubs = $form->submissions()
            ->with(['user', 'dataIsian.kolomFormulir'])
            ->get();
        $issuesMap = $this->quality->inspectSubmissions($form, $allSubs);
        $activeSubs = $allSubs->where('status_validasi', '!=', 'ditolak')->values();
        $remainingIssues = $activeSubs->filter(fn (FormSubmission $s): bool => ! empty($issuesMap[$s->id]))->count();

        return [
            'changed_cells' => $normRes['changed_cells'],
            'changed_rows' => $normRes['changed_rows'],
            'dedup_handled' => $dedupRes['handled'],
            'remaining_issues' => $remainingIssues,
            'active_count' => $activeSubs->count(),
        ];
    }

    /**
     * Compute numeric distribution metrics and IQR-based outlier count for anomaly inspection.
     *
     * @param  array<int, array{sub_code: string, value: float}>  $samples
     * @return array{
     *     count: int,
     *     min: float|null,
     *     median: float|null,
     *     max: float|null,
     *     outlier_count: int,
     *     outlier_codes: array<int, string>
     * }
     */
    protected function computeNumericStats(array $samples): array
    {
        $count = count($samples);
        if ($count === 0) {
            return [
                'count' => 0,
                'min' => null,
                'median' => null,
                'max' => null,
                'outlier_count' => 0,
                'outlier_codes' => [],
            ];
        }

        $vals = array_map(fn (array $s): float => $s['value'], $samples);
        sort($vals);

        $mid = intdiv($count, 2);
        $median = $count % 2 === 1
            ? $vals[$mid]
            : round(($vals[$mid - 1] + $vals[$mid]) / 2, 2);

        $outlierCodes = [];
        if ($count >= 4) {
            $q1 = $this->percentile($vals, 0.25);
            $q3 = $this->percentile($vals, 0.75);
            $iqr = $q3 - $q1;
            if ($iqr > 0) {
                $low = $q1 - 1.5 * $iqr;
                $high = $q3 + 1.5 * $iqr;
                foreach ($samples as $sample) {
                    if ($sample['value'] < $low || $sample['value'] > $high) {
                        $outlierCodes[] = $sample['sub_code'];
                    }
                }
            }
        }

        return [
            'count' => $count,
            'min' => $vals[0],
            'median' => $median,
            'max' => $vals[$count - 1],
            'outlier_count' => count($outlierCodes),
            'outlier_codes' => array_slice($outlierCodes, 0, 5),
        ];
    }

    /**
     * Linear-interpolated percentile over a sorted float array.
     *
     * @param  array<int, float>  $sorted
     */
    protected function percentile(array $sorted, float $p): float
    {
        $n = count($sorted);
        if ($n === 0) {
            return 0.0;
        }
        $pos = ($n - 1) * $p;
        $lo = (int) floor($pos);
        $hi = (int) ceil($pos);
        if ($lo === $hi) {
            return $sorted[$lo];
        }

        return $sorted[$lo] + ($sorted[$hi] - $sorted[$lo]) * ($pos - $lo);
    }

    protected function classifyVisualizationSuitability(
        string $type,
        int $activeCount,
        int $uniqueCount,
        int $filled,
        int $invalid
    ): string {
        if ($activeCount === 0 || $filled === 0) {
            return 'empty';
        }
        if (in_array($type, ['number', 'linear_scale', 'rating'], true)) {
            return $invalid === 0 ? 'metric_ideal' : 'needs_fix';
        }
        if (in_array($type, ['radio', 'select', 'checkbox'], true) && $uniqueCount >= 2 && $uniqueCount <= 12) {
            return 'group_ideal';
        }
        if ($type === 'text' && $uniqueCount >= 2 && $uniqueCount <= 12) {
            return 'group_ideal';
        }
        if ($activeCount >= 3 && $uniqueCount <= 1) {
            return 'low_variance';
        }

        return 'detail_only';
    }

    /**
     * Build the canonical 5-stage Satu Data processing compliance checklist.
     *
     * @return array<int, array{step: int, key: string, title: string, desc: string, done: bool, status_label: string}>
     */
    protected function buildStandardStages(
        DataForm $form,
        int $activeCount,
        int $validCount,
        int $issueRows,
        int $duplicateRows,
        int $missingCells,
        int $invalidCells,
        int $nonUniformCells
    ): array {
        $normDone = $activeCount > 0 && $nonUniformCells === 0 && $invalidCells === 0;
        $dedupDone = $activeCount > 0 && $duplicateRows === 0;
        $missingDone = $activeCount > 0 && $missingCells === 0 && $invalidCells === 0;
        $finalDone = $form->isPreprocessed() && $issueRows === 0 && $validCount > 0;

        return [
            [
                'step' => 1,
                'key' => 'lock',
                'title' => 'Tutup & Kunci Sumber',
                'desc' => 'Instrumen ditutup agar snapshot respons stabil selama pembersihan.',
                'done' => $form->isClosed(),
                'status_label' => $form->isClosed() ? 'Terkunci' : 'Terbuka',
            ],
            [
                'step' => 2,
                'key' => 'normalize',
                'title' => 'Normalisasi Format',
                'desc' => 'Seragamkan spasi, kapitalisasi, pilihan, tanggal ISO, dan angka murni.',
                'done' => $normDone,
                'status_label' => $normDone ? 'Seragam' : ($activeCount === 0 ? 'Menunggu' : "{$nonUniformCells} sel"),
            ],
            [
                'step' => 3,
                'key' => 'dedup',
                'title' => 'Deduplikasi Ganda',
                'desc' => 'Deteksi dan sisihkan baris identik agar frekuensi grafik tidak membengkak.',
                'done' => $dedupDone,
                'status_label' => $dedupDone ? 'Bersih' : ($activeCount === 0 ? 'Menunggu' : "{$duplicateRows} ganda"),
            ],
            [
                'step' => 4,
                'key' => 'missing',
                'title' => 'Missing Value & Validasi',
                'desc' => 'Isi sel kosong (modus/mean/median) atau tolak baris tidak layak.',
                'done' => $missingDone,
                'status_label' => $missingDone ? 'Lengkap' : ($activeCount === 0 ? 'Menunggu' : ($missingCells + $invalidCells).' sel'),
            ],
            [
                'step' => 5,
                'key' => 'finalize',
                'title' => 'Quality Gate & Valid',
                'desc' => 'Finalisasi 0 masalah kualitas dan pertahankan jejak audit nilai mentah.',
                'done' => $finalDone,
                'status_label' => $finalDone ? 'Siap Visualisasi' : ($issueRows > 0 ? "{$issueRows} bermasalah" : 'Klik Finalisasi'),
            ],
        ];
    }

    /**
     * Batch-normalize active submission values while preserving original nilai_mentah.
     *
     * @param  array{normalize_numbers?: bool, text_case?: string}  $opts
     * @return array{changed_cells: int, changed_rows: int}
     */
    public function normalizeForm(DataForm $form, int $actorId, array $opts = []): array
    {
        $form->loadMissing('kolomFormulir');
        $fieldsById = $form->kolomFormulir
            ->filter(fn (FormField $col): bool => $col->isInputField())
            ->keyBy('id');
        $activeSubs = $form->submissions()
            ->with(['dataIsian.kolomFormulir'])
            ->where('status_validasi', '!=', 'ditolak')
            ->get();

        $changedCells = 0;
        $changedRows = 0;

        DB::transaction(function () use ($form, $fieldsById, $activeSubs, $actorId, $opts, &$changedCells, &$changedRows): void {
            $now = now();

            foreach ($activeSubs as $sub) {
                $rowTouched = false;
                $existingByColId = $sub->dataIsian->keyBy('id_kolom');

                foreach ($fieldsById as $colId => $col) {
                    /** @var FormField $col */
                    $fieldDef = $col->toFieldArray();
                    /** @var SubmissionValue|null $sv */
                    $sv = $existingByColId->get($colId);
                    $currentVal = (string) ($sv?->nilai ?? '');
                    $norm = $this->quality->normalizeValue($fieldDef, $currentVal, $opts);
                    $isClean = $this->quality->fieldError($fieldDef, $norm) === null;

                    if ($sv === null) {
                        SubmissionValue::create([
                            'id_pengisian' => $sub->id,
                            'id_kolom' => $colId,
                            'nilai' => $norm,
                            'nilai_mentah' => $currentVal,
                            'status_bersih' => $isClean,
                        ]);
                        if ($norm !== $currentVal) {
                            $changedCells++;
                            $rowTouched = true;
                        }
                        continue;
                    }

                    $updates = [];
                    if ($sv->nilai_mentah === null) {
                        $updates['nilai_mentah'] = $currentVal;
                    }
                    if ($norm !== $currentVal) {
                        $updates['nilai'] = $norm;
                        $changedCells++;
                        $rowTouched = true;
                    }
                    if ((bool) $sv->status_bersih !== $isClean) {
                        $updates['status_bersih'] = $isClean;
                    }

                    if (! empty($updates)) {
                        $sv->update($updates);
                    }
                }

                if ($rowTouched) {
                    $changedRows++;
                    $sub->update([
                        'dibersihkan_oleh' => $actorId,
                        'waktu_dibersihkan' => $now,
                        'status_validasi' => 'mentah',
                    ]);
                }
            }

            $detail = $changedCells > 0
                ? "Normalisasi batch memperbarui {$changedCells} sel pada {$changedRows} baris"
                : 'Normalisasi batch dijalankan (seluruh nilai sudah seragam)';

            $this->persistFormLog($form, 'Normalisasi', $detail, $actorId, $activeSubs->isNotEmpty() ? 'proses' : 'belum');
        });

        return [
            'changed_cells' => $changedCells,
            'changed_rows' => $changedRows,
        ];
    }

    /**
     * Deduplicate active submissions by normalized field values (or per user+values), keeping the newest submission.
     *
     * @return array{handled: int}
     */
    public function deduplicateForm(DataForm $form, int $actorId, string $scope = 'same_values', string $action = 'reject'): array
    {
        $form->loadMissing('kolomFormulir');
        $fields = $form->fields ?? [];
        $activeSubs = $form->submissions()
            ->with(['dataIsian.kolomFormulir'])
            ->where('status_validasi', '!=', 'ditolak')
            ->orderByDesc('waktu_pengisian')
            ->orderByDesc('id')
            ->get();

        $seen = [];
        $duplicates = [];

        foreach ($activeSubs as $sub) {
            $values = $sub->values ?? [];
            $parts = $scope === 'same_user_and_values' ? [$sub->diisi_oleh] : [];
            foreach ($fields as $field) {
                $fid = (string) ($field['id'] ?? '');
                $parts[] = mb_strtolower($this->quality->normalizeValue($field, (string) ($values[$fid] ?? ''), ['normalize_numbers' => true]));
            }
            $signature = json_encode($parts) ?: (string) $sub->id;

            if (isset($seen[$signature])) {
                $duplicates[] = $sub;
            } else {
                $seen[$signature] = $sub->id;
            }
        }

        $handled = count($duplicates);

        DB::transaction(function () use ($form, $duplicates, $actorId, $scope, $action, $handled): void {
            $now = now();
            foreach ($duplicates as $dup) {
                if ($action === 'delete') {
                    foreach ($dup->attachments ?? [] as $path) {
                        if (is_string($path) && $path !== '' && Storage::disk('local')->exists($path)) {
                            Storage::disk('local')->delete($path);
                        }
                    }
                    $dup->delete();
                } else {
                    $dup->update([
                        'status_validasi' => 'ditolak',
                        'catatan_validasi' => 'Duplikat otomatis ditandai pada fase pre-processing.',
                        'dibersihkan_oleh' => $actorId,
                        'waktu_dibersihkan' => $now,
                    ]);
                }
            }

            $scopeLabel = $scope === 'same_user_and_values' ? 'pengirim & isi sama' : 'isi identik';
            $actionLabel = $action === 'delete' ? 'dihapus permanen' : 'ditandai ditolak';
            $detail = $handled > 0
                ? "Deduplikasi ({$scopeLabel}): {$handled} baris ganda {$actionLabel}"
                : "Deduplikasi ({$scopeLabel}): tidak ditemukan baris ganda baru";

            $remaining = $form->submissions()->count();
            $this->persistFormLog($form, 'Deduplikasi', $detail, $actorId, $remaining > 0 ? 'proses' : 'belum');
        });

        return ['handled' => $handled];
    }

    /**
     * Handle missing values on a target column via mean, median, mode, constant value, or row rejection.
     *
     * @return array{ok: bool, affected: int, message: string}
     */
    public function imputeColumn(
        DataForm $form,
        int $actorId,
        string $fieldCode,
        string $strategy,
        ?string $customValue = null
    ): array {
        $form->loadMissing('kolomFormulir');
        /** @var FormField|null $col */
        $col = $form->kolomFormulir->firstWhere('kode_kolom', $fieldCode);
        if (! $col) {
            return ['ok' => false, 'affected' => 0, 'message' => 'Kolom formulir tidak ditemukan.'];
        }

        $fieldDef = $col->toFieldArray();
        $activeSubs = $form->submissions()
            ->with(['dataIsian.kolomFormulir'])
            ->where('status_validasi', '!=', 'ditolak')
            ->get();

        $missingSubs = $activeSubs->filter(
            fn (FormSubmission $sub): bool => trim((string) ($sub->values[$fieldCode] ?? '')) === ''
        )->values();

        if ($missingSubs->isEmpty()) {
            return ['ok' => true, 'affected' => 0, 'message' => "Kolom {$col->label} tidak memiliki nilai kosong pada baris aktif."];
        }

        $fillValue = null;
        if ($strategy !== 'reject_missing') {
            $nonEmptyValues = $activeSubs
                ->map(fn (FormSubmission $sub): string => trim((string) ($sub->values[$fieldCode] ?? '')))
                ->filter(fn (string $v): bool => $v !== '')
                ->values();

            if ($strategy === 'constant') {
                $fillValue = $this->quality->normalizeValue($fieldDef, (string) ($customValue ?? ''), ['normalize_numbers' => true]);
                if ($fillValue === '') {
                    return ['ok' => false, 'affected' => 0, 'message' => 'Masukkan nilai tetap untuk mengisi sel kosong.'];
                }
                $err = $this->quality->fieldError($fieldDef, $fillValue);
                if ($err !== null) {
                    return ['ok' => false, 'affected' => 0, 'message' => "Nilai tetap tidak valid: {$err}"];
                }
            } elseif ($strategy === 'mean' || $strategy === 'median') {
                $nums = $nonEmptyValues
                    ->map(fn (string $v): ?float => ChartPaletteService::parseNumeric($v))
                    ->filter(fn (?float $n): bool => $n !== null)
                    ->map(fn (?float $n): float => (float) $n)
                    ->values()
                    ->all();

                if (empty($nums)) {
                    return ['ok' => false, 'affected' => 0, 'message' => "Tidak ada nilai angka valid pada kolom {$col->label}."];
                }

                if ($strategy === 'mean') {
                    $calc = round(array_sum($nums) / count($nums), 2);
                } else {
                    sort($nums);
                    $mid = intdiv(count($nums), 2);
                    $calc = count($nums) % 2 === 1
                        ? $nums[$mid]
                        : round(($nums[$mid - 1] + $nums[$mid]) / 2, 2);
                }
                $fillValue = fmod($calc, 1.0) === 0.0 ? (string) (int) $calc : (string) $calc;
            } elseif ($strategy === 'mode') {
                $freq = [];
                $canonical = [];
                foreach ($nonEmptyValues as $rawVal) {
                    $norm = $this->quality->normalizeValue($fieldDef, $rawVal);
                    if ($this->quality->fieldError($fieldDef, $norm) !== null) {
                        continue;
                    }
                    $k = mb_strtolower($norm);
                    $freq[$k] = ($freq[$k] ?? 0) + 1;
                    $canonical[$k] = $norm;
                }

                if (empty($freq)) {
                    return ['ok' => false, 'affected' => 0, 'message' => "Tidak ada nilai dominan yang valid pada kolom {$col->label}."];
                }
                arsort($freq);
                $topKey = (string) array_key_first($freq);
                $fillValue = $canonical[$topKey];
            } else {
                return ['ok' => false, 'affected' => 0, 'message' => 'Strategi penanganan missing value tidak dikenali.'];
            }
        }

        $affected = $missingSubs->count();

        DB::transaction(function () use ($form, $col, $missingSubs, $strategy, $fillValue, $actorId, $affected): void {
            $now = now();
            foreach ($missingSubs as $sub) {
                if ($strategy === 'reject_missing') {
                    $sub->update([
                        'status_validasi' => 'ditolak',
                        'catatan_validasi' => "Nilai kolom {$col->label} kosong (ditandai saat pre-processing).",
                        'dibersihkan_oleh' => $actorId,
                        'waktu_dibersihkan' => $now,
                    ]);
                } else {
                    $sv = $sub->dataIsian->firstWhere('id_kolom', $col->id);
                    if ($sv) {
                        $updates = [
                            'nilai' => (string) $fillValue,
                            'status_bersih' => true,
                        ];
                        if ($sv->nilai_mentah === null) {
                            $updates['nilai_mentah'] = (string) ($sv->nilai ?? '');
                        }
                        $sv->update($updates);
                    } else {
                        SubmissionValue::create([
                            'id_pengisian' => $sub->id,
                            'id_kolom' => $col->id,
                            'nilai' => (string) $fillValue,
                            'nilai_mentah' => '',
                            'status_bersih' => true,
                        ]);
                    }
                    $sub->update([
                        'dibersihkan_oleh' => $actorId,
                        'waktu_dibersihkan' => $now,
                        'status_validasi' => 'mentah',
                    ]);
                }
            }

            $detail = $strategy === 'reject_missing'
                ? "Menolak {$affected} baris dengan {$col->label} kosong"
                : "Imputasi {$col->label} ({$strategy} -> {$fillValue}) pada {$affected} baris";

            $this->persistFormLog($form, 'Missing Value', $detail, $actorId, 'proses');
        });

        $msg = $strategy === 'reject_missing'
            ? "{$affected} baris kosong pada kolom {$col->label} ditandai ditolak."
            : "{$affected} sel kosong pada kolom {$col->label} diisi dengan \"{$fillValue}\".";

        return ['ok' => true, 'affected' => $affected, 'message' => $msg];
    }

    /**
     * Validate cleanliness gate and mark active submissions valid so the form is ready for dataset creation.
     *
     * @return array{ok: bool, message: string, valid_count?: int, rejected_count?: int}
     */
    public function finalizeForm(DataForm $form, int $actorId): array
    {
        $form->loadMissing('kolomFormulir');
        $allSubs = $form->submissions()
            ->with(['user', 'dataIsian.kolomFormulir'])
            ->get();

        $activeSubs = $allSubs->where('status_validasi', '!=', 'ditolak')->values();
        if ($activeSubs->isEmpty()) {
            return [
                'ok' => false,
                'message' => 'Tidak ada baris aktif untuk difinalisasi. Impor atau tunggu pengisian minimal 1 baris valid.',
            ];
        }

        $issuesMap = $this->quality->inspectSubmissions($form, $allSubs);
        $dirtyRows = $activeSubs->filter(fn (FormSubmission $s): bool => ! empty($issuesMap[$s->id]))->count();
        if ($dirtyRows > 0) {
            return [
                'ok' => false,
                'message' => "Masih terdapat {$dirtyRows} baris aktif dengan masalah kualitas data. Jalankan normalisasi, deduplikasi, imputasi, atau perbaikan sel terlebih dahulu.",
            ];
        }

        $profile = $this->profileForm($form, $allSubs, $issuesMap);
        $rejectedCount = $allSubs->where('status_validasi', 'ditolak')->count();
        $validCount = $activeSubs->count();

        DB::transaction(function () use ($form, $activeSubs, $actorId, $profile, $validCount, $rejectedCount): void {
            $now = now();
            foreach ($activeSubs as $sub) {
                $sub->update([
                    'status_validasi' => 'valid',
                    'catatan_validasi' => null,
                    'dibersihkan_oleh' => $sub->dibersihkan_oleh ?? $actorId,
                    'waktu_dibersihkan' => $sub->waktu_dibersihkan ?? $now,
                ]);
                $sub->dataIsian()->update(['status_bersih' => true]);
            }

            $this->persistFormLog(
                $form,
                'Finalisasi Pre-processing',
                "Lulus verifikasi kualitas: {$validCount} baris valid siap dikemas menjadi dataset ({$rejectedCount} baris ditolak)",
                $actorId,
                'siap',
                [
                    'valid_count' => $validCount,
                    'rejected_count' => $rejectedCount,
                    'total_count' => $validCount + $rejectedCount,
                    'completeness_avg' => collect($profile['columns'])->avg('completeness_pct') ?? 100.0,
                ]
            );
        });

        return [
            'ok' => true,
            'message' => "Pre-processing selesai: {$validCount} baris dinyatakan valid dan siap dikemas menjadi dataset.",
            'valid_count' => $validCount,
            'rejected_count' => $rejectedCount,
        ];
    }

    public function markFormNeedsPreprocessing(DataForm $form): void
    {
        $total = $form->submissions()->count();
        $form->update([
            'status_preprocessing' => $total > 0 ? 'proses' : 'belum',
        ]);
    }

    /**
     * Append a structured log entry onto formulir.ringkasan_preprocessing.
     *
     * @param  array<string, mixed>|null  $snapshot
     */
    protected function persistFormLog(
        DataForm $form,
        string $action,
        string $detail,
        int $actorId,
        string $nextStatus,
        ?array $snapshot = null
    ): void {
        $form->refresh();
        $summary = is_array($form->ringkasan_preprocessing) ? $form->ringkasan_preprocessing : [];
        $logs = is_array($summary['logs'] ?? null) ? $summary['logs'] : [];
        $logs[] = [
            'action' => $action,
            'detail' => $detail,
            'actor_id' => $actorId,
            'at' => now()->toIso8601String(),
            'at_label' => now()->locale('id')->translatedFormat('j M Y, H.i'),
        ];
        $summary['logs'] = array_slice($logs, -25);
        if ($snapshot !== null) {
            $summary['snapshot'] = $snapshot;
        }

        $form->update([
            'status_preprocessing' => $nextStatus,
            'ringkasan_preprocessing' => $summary,
            'diproses_oleh' => $actorId,
            'waktu_diproses' => now(),
        ]);
    }
}
