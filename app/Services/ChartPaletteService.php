<?php

namespace App\Services;

class ChartPaletteService
{
    /**
     * @var array<string, array<int, string>>
     */
    public const PALETTES = [
        'sky' => ['#0069a8', '#00a6f4', '#74d4ff', '#182654', '#0284c7', '#64748b'],
        'blue' => ['#182654', '#1e3575', '#0069a8', '#00a6f4', '#74d4ff', '#64748b'],
        'indigo' => ['#4f46e5', '#818cf8', '#a5b4fc', '#312e81', '#c7d2fe', '#94a3b8'],
        'cyan' => ['#0891b2', '#22d3ee', '#67e8f9', '#164e63', '#a5f3fc', '#94a3b8'],
        'teal' => ['#0d9488', '#2dd4bf', '#5eead4', '#134e4a', '#99f6e4', '#94a3b8'],
        'violet' => ['#7c3aed', '#a78bfa', '#c4b5fd', '#4c1d95', '#ddd6fe', '#94a3b8'],
    ];

    /**
     * @var array<string, string>
     */
    public const KINDS = [
        'number' => 'Angka',
        'donut' => 'Donat',
        'pie' => 'Pai',
        'bar' => 'Batang',
        'hbar' => 'Horizontal',
        'line' => 'Garis',
        'area' => 'Area',
        'radar' => 'Radar',
    ];

    /**
     * @return array<int, string>
     */
    public static function palette(string $color): array
    {
        return self::PALETTES[$color] ?? self::PALETTES['sky'];
    }

    public static function parseNumeric(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return is_finite((float) $value) ? (float) $value : null;
        }

        $text = preg_replace('/rp|\s|%/i', '', (string) $value) ?? '';
        if ($text === '' || preg_match('/^-?[\d.,]+$/', $text) !== 1) {
            return null;
        }

        if (preg_match('/,\d{1,2}$/', $text) === 1) {
            $normalized = str_replace(',', '.', str_replace('.', '', $text));
        } elseif (preg_match('/^-?[\d,]*\.\d{1,2}$/', $text) === 1) {
            $normalized = str_replace(',', '', $text);
        } else {
            $normalized = str_replace(['.', ','], '', $text);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    /**
     * Return column indexes whose values are all numeric.
     *
     * @param  array<int, string>  $cols
     * @param  array<int, array<int, string|int|float>>  $rows
     * @return array<int, string>
     */
    public static function numericColumns(array $cols, array $rows): array
    {
        if (empty($rows) || empty($cols)) {
            return [];
        }

        $numeric = [];
        foreach ($cols as $idx => $colName) {
            $allNumeric = true;
            foreach ($rows as $row) {
                if (! array_key_exists($idx, $row) || self::parseNumeric($row[$idx]) === null) {
                    $allNumeric = false;
                    break;
                }
            }

            if ($allNumeric) {
                $numeric[(int) $idx] = (string) $colName;
            }
        }

        return $numeric;
    }
}
