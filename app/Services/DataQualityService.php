<?php

namespace App\Services;

use App\Models\DataForm;
use App\Models\FormSubmission;
use DateTimeImmutable;
use Illuminate\Support\Collection;

class DataQualityService
{
    /**
     * Normalize whitespace, standardize choice labels/checkboxes, and optionally clean numbers/case.
     *
     * @param  array<string, mixed>  $field
     * @param  array{normalize_numbers?: bool, text_case?: string}  $options
     */
    public function normalizeValue(array $field, string $value, array $options = []): string
    {
        $type = (string) ($field['type'] ?? 'text');
        if ($type === 'file') {
            return $value;
        }

        if ($type === 'textarea') {
            $lines = preg_split('/\r?\n/', trim($value)) ?: [];
            $cleanedLines = array_map(
                fn (string $line): string => preg_replace('/[^\S\n]+/', ' ', trim($line)) ?? trim($line),
                $lines,
            );

            return implode("\n", $cleanedLines);
        }

        $clean = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        if ($type === 'number' && ($options['normalize_numbers'] ?? false) && $clean !== '') {
            $stripped = preg_replace('/rp|\s|%/iu', '', $clean) ?? $clean;
            if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $stripped) === 1) {
                $stripped = str_replace(',', '.', str_replace('.', '', $stripped));
            } elseif (preg_match('/^-?\d+,\d+$/', $stripped) === 1) {
                $stripped = str_replace(',', '.', $stripped);
            }
            if (is_numeric($stripped)) {
                return $stripped;
            }
        }

        if ($type === 'select' || $type === 'radio') {
            foreach (($field['options'] ?? []) as $opt) {
                if (mb_strtolower((string) $opt) === mb_strtolower($clean)) {
                    return (string) $opt;
                }
            }
        }

        if ($type === 'checkbox') {
            if ($clean === '') {
                return '';
            }
            $tokens = array_values(array_filter(array_map(
                static fn (string $part): string => preg_replace('/\s+/', ' ', trim($part)) ?? trim($part),
                explode(',', $clean)
            ), static fn (string $part): bool => $part !== ''));

            $optionsMap = [];
            foreach (($field['options'] ?? []) as $opt) {
                $optStr = (string) $opt;
                $optionsMap[mb_strtolower($optStr)] = $optStr;
            }

            $normalized = [];
            $seen = [];
            foreach ($tokens as $tok) {
                $lower = mb_strtolower($tok);
                $canon = $optionsMap[$lower] ?? $tok;
                $canonKey = mb_strtolower($canon);
                if (! isset($seen[$canonKey])) {
                    $seen[$canonKey] = true;
                    $normalized[] = $canon;
                }
            }

            return implode(', ', $normalized);
        }

        if ($type === 'text' && $clean !== '') {
            $case = (string) ($options['text_case'] ?? 'none');
            if ($case === 'title') {
                return mb_convert_case($clean, MB_CASE_TITLE, 'UTF-8');
            }
            if ($case === 'upper') {
                return mb_strtoupper($clean, 'UTF-8');
            }
            if ($case === 'lower') {
                return mb_strtolower($clean, 'UTF-8');
            }
        }

        return $clean;
    }

    /**
     * Validate a single field value against its definition.
     *
     * @param  array<string, mixed>  $field
     */
    public function fieldError(array $field, string $value): ?string
    {
        $clean = trim($value);
        $required = (bool) ($field['required'] ?? false);
        $type = (string) ($field['type'] ?? 'text');

        if ($clean === '') {
            return $required ? 'Data wajib masih kosong.' : null;
        }

        if ($type === 'number' && ! is_numeric($clean)) {
            return 'Nilai harus berupa angka yang valid.';
        }

        if ($type === 'date') {
            $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $clean);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $clean) !== 1 || ! $dt || $dt->format('Y-m-d') !== $clean) {
                return 'Tanggal tidak valid.';
            }
        }

        if ($type === 'select' || $type === 'radio') {
            $options = array_map('strval', $field['options'] ?? []);
            if (! in_array($clean, $options, true)) {
                return 'Nilai tidak sesuai pilihan formulir.';
            }
        }

        if ($type === 'checkbox') {
            $options = array_map('strval', $field['options'] ?? []);
            $items = array_values(array_filter(array_map('trim', explode(',', $clean)), static fn (string $x): bool => $x !== ''));
            if (empty($items)) {
                return $required ? 'Data wajib masih kosong.' : null;
            }
            foreach ($items as $item) {
                if (! in_array($item, $options, true)) {
                    return 'Terdapat pilihan kotak centang yang tidak sesuai formulir.';
                }
            }
        }

        if ($type === 'file' && preg_match('/\.(pdf|jpe?g|png)$/i', $clean) !== 1) {
            return 'Format bukti harus PDF, JPG, atau PNG.';
        }

        return null;
    }

    /**
     * Inspect all submissions of a form for duplicates, missing required values, invalid values, and formatting issues.
     * Rejected rows are excluded from blocking active duplicate checks and finalization.
     *
     * @param  Collection<int, FormSubmission>  $submissions
     * @return array<int, array<int, string>>
     */
    public function inspectSubmissions(DataForm $form, Collection $submissions): array
    {
        $fields = $form->fields ?? [];
        $groups = [];

        foreach ($submissions as $sub) {
            if ($sub->status_validasi === 'ditolak') {
                continue;
            }

            $values = $sub->values ?? [];
            $parts = [$sub->diisi_oleh];
            foreach ($fields as $field) {
                $fid = (string) ($field['id'] ?? '');
                $parts[] = mb_strtolower($this->normalizeValue($field, (string) ($values[$fid] ?? '')));
            }
            $key = json_encode($parts) ?: (string) $sub->id;
            $groups[$key][] = $sub->id;
        }

        $duplicateIds = [];
        foreach ($groups as $ids) {
            if (count($ids) > 1) {
                foreach ($ids as $id) {
                    $duplicateIds[$id] = true;
                }
            }
        }

        $result = [];
        foreach ($submissions as $sub) {
            if ($sub->status_validasi === 'ditolak') {
                $result[$sub->id] = [];
                continue;
            }

            $issues = [];
            if (isset($duplicateIds[$sub->id])) {
                $issues[] = 'Data ganda: pengirim dan isi sama. Tinjau atau deduplikasi pada pre-processing.';
            }

            $values = $sub->values ?? [];
            foreach ($fields as $field) {
                $fid = (string) ($field['id'] ?? '');
                $label = (string) ($field['label'] ?? $fid);
                $val = (string) ($values[$fid] ?? '');

                $error = $this->fieldError($field, $val);
                if ($error !== null) {
                    $issues[] = "{$label}: {$error}";
                }

                if ($val !== '' && $this->normalizeValue($field, $val) !== $val) {
                    $issues[] = "{$label}: format belum seragam.";
                }
            }

            $result[$sub->id] = $issues;
        }

        return $result;
    }
}
