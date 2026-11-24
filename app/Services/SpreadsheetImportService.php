<?php

namespace App\Services;

use App\Models\DataForm;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class SpreadsheetImportService
{
    /**
     * Parse an uploaded .csv or .xlsx file into normalized header labels and string rows.
     *
     * @return array{headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    public function parseUploadedFile(string $path, string $extension): array
    {
        $ext = strtolower(trim($extension));

        if ($ext === 'csv' || $ext === 'txt') {
            return $this->parseCsv($path);
        }

        if ($ext === 'xlsx') {
            return $this->parseXlsx($path);
        }

        throw new RuntimeException('Format berkas tidak didukung. Gunakan berkas CSV atau XLSX.');
    }

    /**
     * Map parsed spreadsheet rows to associative `[kode_kolom => value]` payloads for a DataForm.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array<string, string>>
     */
    public function mapRowsToForm(DataForm $form, array $headers, array $rows): array
    {
        $form->loadMissing('kolomFormulir');
        $fields = $form->kolomFormulir
            ->filter(fn ($col): bool => $col->isInputField())
            ->values();

        if ($fields->isEmpty() || empty($rows)) {
            return [];
        }

        $ignoredMeta = [
            'timestamp',
            'waktu',
            'waktu kirim',
            'waktu pengisian',
            'nama pengirim',
            'nama',
            'nim',
            'nim nip',
            'email',
            'email address',
        ];

        $normalize = static function (string $label): string {
            $clean = mb_strtolower(trim($label));
            $clean = preg_replace('/[^a-z0-9\s]+/u', ' ', $clean) ?? $clean;

            return preg_replace('/\s+/', ' ', trim($clean)) ?? trim($clean);
        };

        // Map each spreadsheet column index to a matching kolom_formulir kode_kolom.
        $colIndexToKode = [];
        $dataColIndexes = [];

        foreach ($headers as $idx => $rawHeader) {
            $norm = $normalize((string) $rawHeader);
            if ($norm === '' || in_array($norm, $ignoredMeta, true)) {
                continue;
            }

            $dataColIndexes[] = $idx;
            foreach ($fields as $field) {
                $fieldLabelNorm = $normalize($field->label);
                $fieldKodeNorm = $normalize($field->kode_kolom);
                if ($norm === $fieldKodeNorm || $norm === $fieldLabelNorm) {
                    $colIndexToKode[$idx] = $field->kode_kolom;
                    break;
                }
            }
        }

        // Positional fallback when spreadsheet headers do not match field labels explicitly.
        if (empty($colIndexToKode)) {
            $sourceIndexes = ! empty($dataColIndexes) ? $dataColIndexes : array_keys($headers);
            foreach (array_values($sourceIndexes) as $fieldPos => $colIdx) {
                $targetField = $fields->get($fieldPos);
                if ($targetField !== null) {
                    $colIndexToKode[$colIdx] = $targetField->kode_kolom;
                }
            }
        }

        $mapped = [];
        foreach ($rows as $row) {
            $entry = [];
            $hasAnyNonEmpty = false;

            foreach ($fields as $field) {
                $entry[$field->kode_kolom] = '';
            }

            foreach ($colIndexToKode as $colIdx => $kodeKolom) {
                $val = trim((string) ($row[$colIdx] ?? ''));
                $entry[$kodeKolom] = $val;
                if ($val !== '') {
                    $hasAnyNonEmpty = true;
                }
            }

            if ($hasAnyNonEmpty) {
                $mapped[] = $entry;
            }
        }

        return $mapped;
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    protected function parseCsv(string $path): array
    {
        $content = @file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return ['headers' => [], 'rows' => []];
        }

        // Strip UTF-8 BOM if present.
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        $firstLine = strtok($content, "\r\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $stream = fopen('php://memory', 'r+');
        if ($stream === false) {
            throw new RuntimeException('Gagal membaca aliran berkas CSV.');
        }

        fwrite($stream, $content);
        rewind($stream);

        $allRows = [];
        while (($data = fgetcsv($stream, 0, $delimiter)) !== false) {
            if ($data === [null]) {
                continue;
            }
            $allRows[] = array_map(static fn (?string $cell): string => trim((string) ($cell ?? '')), $data);
        }
        fclose($stream);

        if (empty($allRows)) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = array_shift($allRows) ?: [];

        return [
            'headers' => $headers,
            'rows' => array_values($allRows),
        ];
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    protected function parseXlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Berkas XLSX tidak valid atau rusak.');
        }

        try {
            $sharedStrings = $this->readSharedStrings($zip);
            $sheetPath = $this->resolveFirstWorksheetPath($zip);
            if ($sheetPath === '') {
                return ['headers' => [], 'rows' => []];
            }

            $xml = $zip->getFromName($sheetPath);
            if ($xml === false) {
                return ['headers' => [], 'rows' => []];
            }

            $doc = new DOMDocument;
            if (! @$doc->loadXML($xml)) {
                return ['headers' => [], 'rows' => []];
            }

            $parsedRows = [];
            $maxCol = 0;

            foreach ($doc->getElementsByTagName('row') as $rowEl) {
                /** @var DOMElement $rowEl */
                $cells = [];
                foreach ($rowEl->getElementsByTagName('c') as $cellEl) {
                    /** @var DOMElement $cellEl */
                    $colIdx = $this->columnIndexFromCoord($cellEl->getAttribute('r'));
                    $type = $cellEl->getAttribute('t');
                    $val = '';

                    if ($type === 's') {
                        $vNode = $cellEl->getElementsByTagName('v')->item(0);
                        if ($vNode !== null) {
                            $val = $sharedStrings[(int) $vNode->textContent] ?? '';
                        }
                    } elseif ($type === 'inlineStr') {
                        $isNode = $cellEl->getElementsByTagName('is')->item(0);
                        $val = $isNode !== null ? $isNode->textContent : '';
                    } else {
                        $vNode = $cellEl->getElementsByTagName('v')->item(0);
                        $val = $vNode !== null ? $vNode->textContent : '';
                    }

                    $cells[$colIdx] = trim($val);
                    $maxCol = max($maxCol, $colIdx);
                }

                if (! empty($cells)) {
                    $dense = [];
                    for ($i = 0; $i <= $maxCol; $i++) {
                        $dense[$i] = $cells[$i] ?? '';
                    }
                    $parsedRows[] = $dense;
                }
            }

            if (empty($parsedRows)) {
                return ['headers' => [], 'rows' => []];
            }

            $headers = array_shift($parsedRows) ?: [];

            return [
                'headers' => $headers,
                'rows' => array_values($parsedRows),
            ];
        } finally {
            $zip->close();
        }
    }

    /**
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

    protected function resolveFirstWorksheetPath(ZipArchive $zip): string
    {
        $wbXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($wbXml !== false && $relsXml !== false) {
            $relsDoc = new DOMDocument;
            $wbDoc = new DOMDocument;
            if (@$relsDoc->loadXML($relsXml) && @$wbDoc->loadXML($wbXml)) {
                $relsMap = [];
                foreach ($relsDoc->getElementsByTagName('Relationship') as $rel) {
                    /** @var DOMElement $rel */
                    $relsMap[$rel->getAttribute('Id')] = $rel->getAttribute('Target');
                }
                foreach ($wbDoc->getElementsByTagName('sheet') as $sheet) {
                    /** @var DOMElement $sheet */
                    $rid = $sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
                    if (isset($relsMap[$rid])) {
                        $target = ltrim($relsMap[$rid], '/');

                        return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
                    }
                }
            }
        }

        return $zip->locateName('xl/worksheets/sheet1.xml') !== false
            ? 'xl/worksheets/sheet1.xml'
            : '';
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
}
