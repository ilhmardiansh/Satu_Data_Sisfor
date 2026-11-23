<?php

namespace App\Services;

use App\Models\DataForm;
use App\Models\FormSubmission;
use DateTimeImmutable;
use Illuminate\Support\Collection;

class DataQualityService
{
    /**
     * Normalize whitespace and canonicalize select options.
     *
     * @param  array<string, mixed>  $field
     */
    public function normalizeValue(array $field, string $value): string
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

        if ($type === 'select') {
            $options = $field['options'] ?? [];
            foreach ($options as $opt) {
                if (mb_strtolower((string) $opt) === mb_strtolower($clean)) {
                    return (string) $opt;
                }
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

        if ($type === 'select') {
            $options = array_map('strval', $field['options'] ?? []);
            if (! in_array($clean, $options, true)) {
                return 'Nilai tidak sesuai pilihan formulir.';
            }
        }

        if ($type === 'file' && preg_match('/\.(pdf|jpe?g|png)$/i', $clean) !== 1) {
            return 'Format bukti harus PDF, JPG, atau PNG.';
        }

        return null;
    }

    /**
     * Inspect all submissions of a form for duplicates, missing required values, invalid values, and formatting issues.
     *
     * @param  Collection<int, FormSubmission>  $submissions
     * @return array<int, array<int, string>>
     */
    public function inspectSubmissions(DataForm $form, Collection $submissions): array
    {
        $fields = $form->fields ?? [];
        $groups = [];

        foreach ($submissions as $sub) {
            $values = $sub->values ?? [];
            $parts = [$sub->user_id];
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
            $issues = [];
            if (isset($duplicateIds[$sub->id])) {
                $issues[] = 'Data ganda: pengirim dan isi sama. Tinjau sebelum menghapus.';
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
