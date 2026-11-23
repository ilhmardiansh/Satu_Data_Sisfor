<?php

namespace App\Services;

use App\Models\DataForm;
use App\Models\FormSubmission;
use Illuminate\Support\Collection;

class DatasetBuilderService
{
    /**
     * Build dataset columns and rows from form submissions.
     *
     * @param  Collection<int, FormSubmission>  $submissions
     * @param  array<int, string>  $pickedCols
     * @return array{cols: array<int, string>, rows: array<int, array<int, string|int>>}
     */
    public function buildFromForm(
        DataForm $form,
        Collection $submissions,
        string $mode,
        ?string $groupFieldId,
        array $pickedCols = []
    ): array {
        if ($mode === 'rekap') {
            $fields = collect($form->fields ?? []);
            $field = $fields->firstWhere('id', $groupFieldId);
            $counts = [];

            foreach ($submissions as $sub) {
                $val = trim((string) ($sub->values[$groupFieldId] ?? ''));
                $key = $val !== '' ? $val : '(kosong)';
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }

            foreach (($field['options'] ?? []) as $opt) {
                $optStr = (string) $opt;
                if (! array_key_exists($optStr, $counts)) {
                    $counts[$optStr] = 0;
                }
            }

            $rows = [];
            foreach ($counts as $k => $v) {
                $rows[] = [(string) $k, (int) $v];
            }

            return [
                'cols' => [(string) ($field['label'] ?? 'Kategori'), 'Jumlah'],
                'rows' => $rows,
            ];
        }

        $selectedCols = array_values(array_filter(
            $form->sourceCols(),
            fn (array $col): bool => in_array($col['id'], $pickedCols, true)
        ));

        $cols = array_map(fn (array $col): string => $col['label'], $selectedCols);
        $rows = [];

        foreach ($submissions as $sub) {
            $row = [];
            foreach ($selectedCols as $col) {
                $cid = $col['id'];
                if ($cid === '_nama') {
                    $row[] = $sub->user?->name ?? '-';
                } elseif ($cid === '_nim') {
                    $row[] = $sub->user?->nim ?? '-';
                } elseif ($cid === '_at') {
                    $row[] = $sub->submittedFormatted();
                } else {
                    $row[] = (string) ($sub->values[$cid] ?? '');
                }
            }
            $rows[] = $row;
        }

        return [
            'cols' => $cols,
            'rows' => $rows,
        ];
    }
}
