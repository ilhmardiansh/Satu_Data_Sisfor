<?php

namespace App\Services;

use App\Models\DataForm;
use App\Models\FormField;
use App\Models\FormSubmission;
use DateTimeImmutable;
use Illuminate\Support\Collection;

class DataQualityService
{
    /**
     * @var array<string, array<int, string>>
     */
    public const FILE_TYPE_EXTENSIONS = [
        'pdf' => ['pdf'],
        'image' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg', 'heic'],
        'document' => ['doc', 'docx', 'odt', 'txt', 'rtf', 'pdf'],
        'spreadsheet' => ['xls', 'xlsx', 'ods', 'csv'],
        'presentation' => ['ppt', 'pptx', 'odp', 'pdf'],
        'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
        'video' => ['mp4', 'mov', 'avi', 'mkv', 'webm'],
        'audio' => ['mp3', 'wav', 'm4a', 'ogg', 'aac'],
    ];

    /**
     * Server-side dangerous script/executable extensions blocked even when all file types are permitted.
     *
     * @var array<int, string>
     */
    public const BLOCKED_FILE_EXTENSIONS = [
        'php', 'phtml', 'phar', 'exe', 'bat', 'cmd', 'sh', 'com', 'msi', 'vbs', 'js', 'jsp', 'asp', 'aspx',
    ];

    /**
     * Resolve allowed lowercase file extensions for a file-upload field, or null when unrestricted.
     *
     * @param  array<string, mixed>  $field
     * @return array<int, string>|null
     */
    public function allowedFileExtensions(array $field): ?array
    {
        $config = is_array($field['config'] ?? null) ? $field['config'] : [];
        if (($config['restrict_file_types'] ?? false) && ! empty($config['file_types']) && is_array($config['file_types'])) {
            $exts = [];
            foreach ($config['file_types'] as $group) {
                $key = strtolower(trim((string) $group));
                if (isset(self::FILE_TYPE_EXTENSIONS[$key])) {
                    foreach (self::FILE_TYPE_EXTENSIONS[$key] as $ext) {
                        $exts[$ext] = true;
                    }
                }
            }
            if (! empty($exts)) {
                return array_keys($exts);
            }
        }

        return null;
    }

    /**
     * Validate an uploaded filename against optional category restrictions and executable blocklist.
     *
     * @param  array<string, mixed>  $field
     */
    public function fileNameError(array $field, string $fileName): ?string
    {
        $cleanName = trim($fileName);
        if ($cleanName === '') {
            return 'Nama berkas tidak valid.';
        }

        $ext = strtolower((string) pathinfo($cleanName, PATHINFO_EXTENSION));
        if ($ext !== '' && in_array($ext, self::BLOCKED_FILE_EXTENSIONS, true)) {
            return 'Jenis berkas eksekusi tidak diperbolehkan demi keamanan.';
        }

        $allowed = $this->allowedFileExtensions($field);
        if ($allowed !== null && ($ext === '' || ! in_array($ext, $allowed, true))) {
            $allowedList = strtoupper(implode(', ', $allowed));

            return "Format berkas harus berupa: {$allowedList}.";
        }

        return null;
    }

    /**
     * Parse canonical `"Row: Col1, Col2; Row2: Col3"` grid string into `[row => [col, ...]]`.
     *
     * @return array<string, array<int, string>>
     */
    public function parseGridValue(string $value): array
    {
        $clean = trim($value);
        if ($clean === '') {
            return [];
        }

        $parsed = [];
        foreach (explode(';', $clean) as $entry) {
            $entry = trim($entry);
            if ($entry === '' || ! str_contains($entry, ':')) {
                continue;
            }
            [$rowLabel, $colPart] = explode(':', $entry, 2);
            $rowLabel = preg_replace('/\s+/', ' ', trim($rowLabel)) ?? trim($rowLabel);
            if ($rowLabel === '') {
                continue;
            }
            $cols = array_values(array_filter(array_map(
                static fn (string $c): string => preg_replace('/\s+/', ' ', trim($c)) ?? trim($c),
                explode(',', $colPart)
            ), static fn (string $c): bool => $c !== ''));

            if (! empty($cols)) {
                $parsed[$rowLabel] = $cols;
            }
        }

        return $parsed;
    }

    /**
     * Normalize whitespace, standardize choice labels/checkboxes, and optionally clean numbers/case.
     *
     * @param  array<string, mixed>  $field
     * @param  array{normalize_numbers?: bool, text_case?: string}  $options
     */
    public function normalizeValue(array $field, string $value, array $options = []): string
    {
        $type = (string) ($field['type'] ?? 'text');
        if ($type === 'file' || in_array($type, FormField::NON_INPUT_TYPES, true)) {
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

        if ($type === 'mc_grid' || $type === 'checkbox_grid') {
            $parsed = $this->parseGridValue($clean);
            if (empty($parsed)) {
                return $clean;
            }
            $segments = [];
            foreach ($parsed as $rowLabel => $cols) {
                $segments[] = $rowLabel.': '.implode(', ', $cols);
            }

            return implode('; ', $segments);
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
     * Validate a single field value against its definition and GForm validation rules.
     *
     * @param  array<string, mixed>  $field
     */
    public function fieldError(array $field, string $value): ?string
    {
        $type = (string) ($field['type'] ?? 'text');
        if (in_array($type, FormField::NON_INPUT_TYPES, true)) {
            return null;
        }

        $clean = trim($value);
        $required = (bool) ($field['required'] ?? false);
        $config = is_array($field['config'] ?? null) ? $field['config'] : [];

        if ($clean === '') {
            if (($type === 'mc_grid' || $type === 'checkbox_grid') && ($config['require_row_response'] ?? false)) {
                return 'Setiap baris pada kisi wajib memiliki minimal satu jawaban.';
            }

            return $required ? 'Data wajib masih kosong.' : null;
        }

        if ($type === 'number' && ! is_numeric($clean)) {
            return 'Nilai harus berupa angka yang valid.';
        }

        if ($type === 'linear_scale') {
            if (preg_match('/^-?\d+$/', $clean) !== 1) {
                return 'Pilih angka pada skala linier.';
            }
            $min = (int) ($config['scale_min'] ?? 1);
            $max = (int) ($config['scale_max'] ?? 5);
            $num = (int) $clean;
            if ($num < $min || $num > $max) {
                return "Nilai skala harus antara {$min} hingga {$max}.";
            }
        }

        if ($type === 'rating') {
            if (preg_match('/^\d+$/', $clean) !== 1) {
                return 'Pilih peringkat yang valid.';
            }
            $max = (int) ($config['rating_max'] ?? 5);
            $num = (int) $clean;
            if ($num < 1 || $num > $max) {
                return "Peringkat harus antara 1 hingga {$max}.";
            }
        }

        if ($type === 'date') {
            $includeTime = (bool) ($config['include_time'] ?? false);
            $includeYear = (bool) ($config['include_year'] ?? true);

            if ($includeTime) {
                $dtFull = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $clean)
                    ?: DateTimeImmutable::createFromFormat('!Y-m-d H:i', $clean)
                    ?: DateTimeImmutable::createFromFormat('!Y-m-d', $clean);
                if (! $dtFull) {
                    return 'Tanggal dan waktu tidak valid.';
                }
            } elseif (! $includeYear) {
                $dtNoYear = DateTimeImmutable::createFromFormat('!m-d', $clean)
                    ?: DateTimeImmutable::createFromFormat('!Y-m-d', $clean);
                if (! $dtNoYear) {
                    return 'Tanggal (bulan-hari) tidak valid.';
                }
            } else {
                $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $clean);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $clean) !== 1 || ! $dt || $dt->format('Y-m-d') !== $clean) {
                    return 'Tanggal tidak valid.';
                }
            }
        }

        if ($type === 'time') {
            $mode = (string) ($config['time_mode'] ?? 'time');
            if ($mode === 'duration') {
                if (preg_match('/^\d{1,3}:[0-5]\d(:[0-5]\d)?$/', $clean) !== 1) {
                    return 'Format durasi tidak valid (gunakan JJ:MM:DD).';
                }
            } elseif (preg_match('/^([01]?\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $clean) !== 1) {
                return 'Format waktu tidak valid (gunakan JJ:MM).';
            }
        }

        if ($type === 'select' || $type === 'radio') {
            $options = array_map('strval', $field['options'] ?? []);
            $allowOther = $type === 'radio' && (bool) ($config['allow_other'] ?? false);
            if (! in_array($clean, $options, true) && ! ($allowOther && $clean !== '')) {
                return 'Nilai tidak sesuai pilihan formulir.';
            }
        }

        if ($type === 'checkbox') {
            $options = array_map('strval', $field['options'] ?? []);
            $allowOther = (bool) ($config['allow_other'] ?? false);
            $items = array_values(array_filter(array_map('trim', explode(',', $clean)), static fn (string $x): bool => $x !== ''));
            if (empty($items)) {
                return $required ? 'Data wajib masih kosong.' : null;
            }
            if (! $allowOther) {
                foreach ($items as $item) {
                    if (! in_array($item, $options, true)) {
                        return 'Terdapat pilihan kotak centang yang tidak sesuai formulir.';
                    }
                }
            }

            $valCfg = is_array($config['validation'] ?? null) ? $config['validation'] : [];
            if (! empty($valCfg['enabled'])) {
                $op = (string) ($valCfg['operator'] ?? '');
                $limit = (int) ($valCfg['value'] ?? 0);
                $customMsg = trim((string) ($valCfg['message'] ?? ''));
                $count = count($items);
                if ($op === 'min_select' && $limit > 0 && $count < $limit) {
                    return $customMsg !== '' ? $customMsg : "Pilih minimal {$limit} opsi.";
                }
                if ($op === 'max_select' && $limit > 0 && $count > $limit) {
                    return $customMsg !== '' ? $customMsg : "Pilih maksimal {$limit} opsi.";
                }
                if ($op === 'exact_select' && $limit > 0 && $count !== $limit) {
                    return $customMsg !== '' ? $customMsg : "Pilih tepat {$limit} opsi.";
                }
            }
        }

        if ($type === 'mc_grid' || $type === 'checkbox_grid') {
            $gridRows = array_values(array_filter(array_map('strval', $config['grid_rows'] ?? [])));
            $gridCols = array_values(array_filter(array_map('strval', $config['grid_cols'] ?? [])));
            $parsed = $this->parseGridValue($clean);

            if (empty($parsed)) {
                return ($required || ($config['require_row_response'] ?? false))
                    ? 'Lengkapi pilihan pada kisi.'
                    : 'Format jawaban kisi tidak valid.';
            }

            $usedCols = [];
            foreach ($parsed as $rowLabel => $selectedCols) {
                if ($type === 'mc_grid' && count($selectedCols) > 1) {
                    return "Baris \"{$rowLabel}\" hanya memperbolehkan satu pilihan.";
                }
                foreach ($selectedCols as $colLabel) {
                    if (! empty($gridCols) && ! in_array($colLabel, $gridCols, true)) {
                        return "Pilihan kolom \"{$colLabel}\" tidak valid pada baris \"{$rowLabel}\".";
                    }
                    if (($config['limit_one_per_col'] ?? false) && isset($usedCols[$colLabel])) {
                        return 'Batasi satu respons per kolom: pilih kolom yang berbeda di setiap baris.';
                    }
                    $usedCols[$colLabel] = true;
                }
            }

            if ($required || ($config['require_row_response'] ?? false)) {
                foreach ($gridRows as $rowLabel) {
                    if (empty($parsed[$rowLabel])) {
                        return "Baris \"{$rowLabel}\" wajib diisi.";
                    }
                }
            }
        }

        if ($type === 'file') {
            $names = array_values(array_filter(array_map('trim', explode(',', $clean)), static fn (string $n): bool => $n !== ''));
            foreach ($names as $name) {
                $nameErr = $this->fileNameError($field, $name);
                if ($nameErr !== null) {
                    return $nameErr;
                }
            }
        }

        if (in_array($type, ['text', 'textarea', 'number'], true)) {
            $valCfg = is_array($config['validation'] ?? null) ? $config['validation'] : [];
            if (! empty($valCfg['enabled'])) {
                $customErr = $this->customValidationError($valCfg, $clean);
                if ($customErr !== null) {
                    return $customErr;
                }
            }
        }

        return null;
    }

    /**
     * Evaluate Google Forms custom response validation rule (number, text, length, regex).
     *
     * @param  array<string, mixed>  $valCfg
     */
    protected function customValidationError(array $valCfg, string $clean): ?string
    {
        $category = (string) ($valCfg['category'] ?? 'text');
        $op = (string) ($valCfg['operator'] ?? '');
        $val1 = (string) ($valCfg['value'] ?? '');
        $val2 = (string) ($valCfg['value2'] ?? '');
        $customMsg = trim((string) ($valCfg['message'] ?? ''));
        $fallback = static fn (string $default): string => $customMsg !== '' ? $customMsg : $default;

        if ($category === 'number') {
            if (! is_numeric($clean)) {
                return $fallback('Nilai harus berupa angka.');
            }
            $num = (float) $clean;
            $a = is_numeric($val1) ? (float) $val1 : 0.0;
            $b = is_numeric($val2) ? (float) $val2 : 0.0;
            $low = min($a, $b);
            $high = max($a, $b);

            return match ($op) {
                'gt' => $num > $a ? null : $fallback("Angka harus lebih besar dari {$a}."),
                'gte' => $num >= $a ? null : $fallback("Angka harus lebih besar atau sama dengan {$a}."),
                'lt' => $num < $a ? null : $fallback("Angka harus kurang dari {$a}."),
                'lte' => $num <= $a ? null : $fallback("Angka harus kurang atau sama dengan {$a}."),
                'eq' => $num === $a ? null : $fallback("Angka harus sama dengan {$a}."),
                'neq' => $num !== $a ? null : $fallback("Angka tidak boleh sama dengan {$a}."),
                'between' => ($num >= $low && $num <= $high) ? null : $fallback("Angka harus berada di antara {$low} dan {$high}."),
                'not_between' => ($num < $low || $num > $high) ? null : $fallback("Angka tidak boleh berada di antara {$low} dan {$high}."),
                'whole_number' => preg_match('/^-?\d+$/', $clean) === 1 ? null : $fallback('Harus berupa bilangan bulat.'),
                default => null,
            };
        }

        if ($category === 'text') {
            return match ($op) {
                'contains' => $val1 === '' || mb_stripos($clean, $val1) !== false ? null : $fallback("Teks harus mengandung \"{$val1}\"."),
                'not_contains' => $val1 === '' || mb_stripos($clean, $val1) === false ? null : $fallback("Teks tidak boleh mengandung \"{$val1}\"."),
                'email' => filter_var($clean, FILTER_VALIDATE_EMAIL) !== false ? null : $fallback('Masukkan alamat email yang valid.'),
                'url' => filter_var($clean, FILTER_VALIDATE_URL) !== false ? null : $fallback('Masukkan tautan URL yang valid (mis. https://...).'),
                default => null,
            };
        }

        if ($category === 'length') {
            $len = mb_strlen($clean, 'UTF-8');
            $limit = max(1, (int) $val1);

            return match ($op) {
                'max_chars' => $len <= $limit ? null : $fallback("Panjang teks maksimal {$limit} karakter."),
                'min_chars' => $len >= $limit ? null : $fallback("Panjang teks minimal {$limit} karakter."),
                default => null,
            };
        }

        if ($category === 'regex' && $val1 !== '') {
            $delimiter = '~';
            $escaped = str_replace($delimiter, '\\'.$delimiter, $val1);
            $pattern = $delimiter.$escaped.$delimiter.'u';
            $matched = @preg_match($pattern, $clean);
            if ($matched === false) {
                return null;
            }

            return match ($op) {
                'contains' => $matched === 1 ? null : $fallback('Isian tidak memenuhi pola yang diminta.'),
                'not_contains' => $matched === 0 ? null : $fallback('Isian mengandung pola yang tidak diperbolehkan.'),
                'matches' => @preg_match($delimiter.'\A(?:'.$escaped.')\z'.$delimiter.'u', $clean) === 1
                    ? null
                    : $fallback('Format isian tidak sesuai pola.'),
                'not_matches' => @preg_match($delimiter.'\A(?:'.$escaped.')\z'.$delimiter.'u', $clean) === 0
                    ? null
                    : $fallback('Isian tidak diperbolehkan cocok dengan pola terlarang.'),
                default => null,
            };
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
            $activeFieldIds = $this->resolveActiveFieldIds($form->items ?? [], $values);

            foreach ($fields as $field) {
                $fid = (string) ($field['id'] ?? '');
                $label = (string) ($field['label'] ?? $fid);
                $val = (string) ($values[$fid] ?? '');

                if (! isset($activeFieldIds[$fid]) && trim($val) === '') {
                    continue;
                }

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

    /**
     * Trace section navigation using a submission's answer map to determine which field IDs were active.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $values
     * @return array<string, bool>
     */
    public function resolveActiveFieldIds(array $items, array $values): array
    {
        $sections = [
            ['id' => '__start__', 'after_action' => 'next', 'fields' => []],
        ];
        $currentIdx = 0;

        foreach ($items as $item) {
            $type = (string) ($item['type'] ?? 'text');
            if ($type === 'section') {
                if (empty($sections[0]['fields']) && count($sections) === 1 && $sections[0]['id'] === '__start__') {
                    $sections[0] = [
                        'id' => (string) ($item['id'] ?? 'sec_0'),
                        'after_action' => (string) ($item['config']['after_action'] ?? 'next'),
                        'fields' => [],
                    ];
                } else {
                    $sections[] = [
                        'id' => (string) ($item['id'] ?? 'sec_'.count($sections)),
                        'after_action' => (string) ($item['config']['after_action'] ?? 'next'),
                        'fields' => [],
                    ];
                    $currentIdx = count($sections) - 1;
                }
                continue;
            }

            if (! in_array($type, FormField::NON_INPUT_TYPES, true)) {
                $sections[$currentIdx]['fields'][] = $item;
            }
        }

        $sectionIndexById = [];
        foreach ($sections as $idx => $sec) {
            $sectionIndexById[$sec['id']] = $idx;
        }

        $activeIds = [];
        $visited = [];
        $cursor = 0;
        $maxSteps = count($sections) + 2;

        while ($cursor >= 0 && $cursor < count($sections) && ! isset($visited[$cursor]) && $maxSteps > 0) {
            $visited[$cursor] = true;
            $maxSteps--;
            $sec = $sections[$cursor];
            $nextTarget = $sec['after_action'] ?? 'next';

            foreach ($sec['fields'] as $fld) {
                $fid = (string) ($fld['id'] ?? '');
                $activeIds[$fid] = true;

                $branchMap = $fld['config']['branching'] ?? null;
                $ans = trim((string) ($values[$fid] ?? ''));
                if (
                    is_array($branchMap)
                    && $ans !== ''
                    && in_array((string) ($fld['type'] ?? ''), ['radio', 'select'], true)
                    && isset($branchMap[$ans])
                    && $branchMap[$ans] !== ''
                ) {
                    $nextTarget = (string) $branchMap[$ans];
                }
            }

            if ($nextTarget === 'submit') {
                break;
            }

            if ($nextTarget !== 'next' && isset($sectionIndexById[$nextTarget])) {
                $cursor = $sectionIndexById[$nextTarget];
            } else {
                $cursor++;
            }
        }

        return $activeIds;
    }
}
