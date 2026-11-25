<?php

namespace App\Services;

use App\Models\DataForm;
use App\Models\FormSubmission;
use Illuminate\Support\Collection;

class DatasetBuilderService
{
    /**
     * Build dataset columns and rows from pre-processed form submissions.
     * Supports grouped aggregations (count, sum, avg, min, max) in 'rekap' mode
     * and column aliasing + sensitive column masking in 'rinci' mode.
     *
     * @param  Collection<int, FormSubmission>  $submissions
     * @param  array<int, string>  $pickedCols
     * @param  array{
     *     agg_func?: string,
     *     agg_field_id?: ?string,
     *     col_aliases?: array<string, string>,
     *     masked_cols?: array<int, string>
     * }  $options
     * @return array{cols: array<int, string>, rows: array<int, array<int, string|int|float>>}
     */
    public function buildFromForm(
        DataForm $form,
        Collection $submissions,
        string $mode,
        ?string $groupFieldId,
        array $pickedCols = [],
        array $options = []
    ): array {
        if ($mode === 'rekap') {
            $fields = collect($form->fields ?? []);
            $groupField = $fields->firstWhere('id', $groupFieldId);
            $aggFunc = in_array($options['agg_func'] ?? 'count', ['count', 'sum', 'avg', 'min', 'max'], true)
                ? (string) $options['agg_func']
                : 'count';
            $aggFieldId = ($options['agg_field_id'] ?? null) !== '' ? ($options['agg_field_id'] ?? null) : null;
            $aggField = $aggFieldId ? $fields->firstWhere('id', $aggFieldId) : null;

            if ($aggFunc !== 'count' && ! $aggField) {
                $aggFunc = 'count';
            }

            /** @var array<string, array{count: int, nums: array<int, float>}> $buckets */
            $buckets = [];

            foreach ($submissions as $sub) {
                $rawGroup = trim((string) ($sub->values[$groupFieldId] ?? ''));
                $keys = [];
                if (($groupField['type'] ?? '') === 'checkbox' && $rawGroup !== '') {
                    $keys = array_values(array_filter(array_map('trim', explode(',', $rawGroup)), static fn (string $k): bool => $k !== ''));
                }
                if (empty($keys)) {
                    $keys = [$rawGroup !== '' ? $rawGroup : '(kosong)'];
                }

                $metricNum = $aggFieldId
                    ? ChartPaletteService::parseNumeric($sub->values[$aggFieldId] ?? null)
                    : null;

                foreach ($keys as $key) {
                    if (! isset($buckets[$key])) {
                        $buckets[$key] = ['count' => 0, 'nums' => []];
                    }
                    $buckets[$key]['count']++;
                    if ($metricNum !== null) {
                        $buckets[$key]['nums'][] = $metricNum;
                    }
                }
            }

            foreach (($groupField['options'] ?? []) as $opt) {
                $optStr = (string) $opt;
                if (! array_key_exists($optStr, $buckets)) {
                    $buckets[$optStr] = ['count' => 0, 'nums' => []];
                }
            }

            $rows = [];
            foreach ($buckets as $key => $bucket) {
                $val = $this->computeAggregate($aggFunc, $bucket['count'], $bucket['nums']);
                $rows[] = [(string) $key, $val];
            }

            $aggFieldLabel = (string) ($aggField['label'] ?? 'Nilai');
            $metricLabel = match ($aggFunc) {
                'sum' => "Total {$aggFieldLabel}",
                'avg' => "Rata-rata {$aggFieldLabel}",
                'min' => "Minimum {$aggFieldLabel}",
                'max' => "Maksimum {$aggFieldLabel}",
                default => 'Jumlah',
            };

            return [
                'cols' => [(string) ($groupField['label'] ?? 'Kategori'), $metricLabel],
                'rows' => $rows,
            ];
        }

        $selectedCols = array_values(array_filter(
            $form->sourceCols(),
            fn (array $col): bool => in_array($col['id'], $pickedCols, true)
        ));

        $colAliases = is_array($options['col_aliases'] ?? null) ? $options['col_aliases'] : [];
        $maskedSet = array_flip(array_values(array_map('strval', $options['masked_cols'] ?? [])));

        $cols = array_map(function (array $col) use ($colAliases): string {
            $alias = trim((string) ($colAliases[$col['id']] ?? ''));

            return $alias !== '' ? $alias : $col['label'];
        }, $selectedCols);

        $rows = [];
        foreach ($submissions->values() as $idx => $sub) {
            $row = [];
            foreach ($selectedCols as $col) {
                $cid = $col['id'];
                if ($cid === '_nama') {
                    $raw = $sub->user?->name ?? '-';
                } elseif ($cid === '_nim') {
                    $raw = $sub->user?->nim ?? '-';
                } elseif ($cid === '_at') {
                    $raw = $sub->submittedFormatted();
                } else {
                    $raw = (string) ($sub->values[$cid] ?? '');
                }

                if (isset($maskedSet[$cid])) {
                    $raw = $cid === '_nama'
                        ? 'Responden #'.str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT)
                        : $this->maskSensitiveValue($raw);
                }

                $row[] = $raw;
            }
            $rows[] = $row;
        }

        return [
            'cols' => $cols,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<int, float>  $nums
     */
    protected function computeAggregate(string $func, int $count, array $nums): int|float
    {
        if ($func === 'count') {
            return $count;
        }

        if (empty($nums)) {
            return 0;
        }

        $raw = match ($func) {
            'sum' => array_sum($nums),
            'avg' => array_sum($nums) / count($nums),
            'min' => min($nums),
            'max' => max($nums),
            default => (float) $count,
        };

        $rounded = round($raw, 2);

        return fmod($rounded, 1.0) === 0.0 ? (int) $rounded : $rounded;
    }

    protected function maskSensitiveValue(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '' || $trimmed === '-') {
            return $trimmed;
        }

        $len = mb_strlen($trimmed);
        if ($len <= 5) {
            return mb_substr($trimmed, 0, 1).str_repeat('*', max(3, $len - 1));
        }

        return mb_substr($trimmed, 0, 3).'***'.mb_substr($trimmed, -2);
    }

    /**
     * Build shared JSON payload of source forms for DatasetBuilder modal.
     * Only pre-processed valid submissions are exposed for dataset construction.
     *
     * @param  Collection<int, DataForm>  $forms
     * @return array<int, array<string, mixed>>
     */
    public function builderFormsPayload(Collection $forms, DataQualityService $quality): array
    {
        return $forms->map(function (DataForm $form) use ($quality): array {
            $validSubs = $form->submissions()
                ->with(['user', 'dataIsian.kolomFormulir'])
                ->where('status_validasi', 'valid')
                ->orderByDesc('waktu_pengisian')
                ->get();

            $totalSubsCount = $form->submissions()->count();
            $rawSubsCount = $form->submissions()->where('status_validasi', 'mentah')->count();
            $issuesMap = $quality->inspectSubmissions($form, $validSubs);
            $dirtyValid = collect($issuesMap)->contains(fn (array $issues): bool => ! empty($issues));
            $fields = $form->fields ?? [];

            $numericFields = array_values(array_filter(
                $fields,
                fn (array $fl): bool => in_array($fl['type'] ?? '', ['number', 'linear_scale', 'rating'], true)
            ));
            $groupableFields = array_values(array_map(function (array $fl) use ($validSubs): array {
                $fid = (string) ($fl['id'] ?? '');
                $distinct = [];
                foreach ($validSubs as $sub) {
                    $raw = trim((string) ($sub->values[$fid] ?? ''));
                    if (($fl['type'] ?? '') === 'checkbox' && $raw !== '') {
                        foreach (explode(',', $raw) as $part) {
                            $tok = trim($part);
                            if ($tok !== '') {
                                $distinct[mb_strtolower($tok)] = true;
                            }
                        }
                    } else {
                        $distinct[mb_strtolower($raw !== '' ? $raw : '(kosong)')] = true;
                    }
                }
                foreach ($fl['options'] ?? [] as $opt) {
                    $distinct[mb_strtolower((string) $opt)] = true;
                }
                $fl['uniqueCount'] = count($distinct);

                return $fl;
            }, array_values(array_filter(
                $fields,
                fn (array $fl): bool => in_array($fl['type'] ?? '', ['select', 'radio', 'checkbox', 'text', 'linear_scale', 'rating'], true)
            ))));

            return [
                'id' => $form->id,
                'code' => $form->code,
                'title' => $form->judul,
                'desc' => $form->deskripsi ?? '',
                'category_id' => $form->id_kategori,
                'kategoriCode' => $form->category?->code ?? '',
                'categoryName' => $form->category?->name ?? '',
                'tahun' => $form->tahun,
                'periode' => $form->periode,
                'statusPreprocessing' => $form->status_preprocessing ?? 'belum',
                'preprocessed' => $form->isPreprocessed(),
                'totalCount' => $totalSubsCount,
                'rawCount' => $rawSubsCount,
                'validCount' => $validSubs->count(),
                'preprocessUrl' => route('panel.preprocessing.show', $form->code),
                'fields' => $fields,
                'numericFields' => $numericFields,
                'groupableFields' => $groupableFields,
                'sourceCols' => $form->sourceCols(),
                'dirty' => $dirtyValid,
                'subs' => $validSubs->map(fn (FormSubmission $s): array => [
                    'nama' => $s->user?->name ?? '-',
                    'nim' => $s->user?->nim ?? '-',
                    'at' => $s->submittedFormatted(),
                    'values' => $s->values ?? [],
                ])->values()->all(),
            ];
        })->values()->all();
    }
}
