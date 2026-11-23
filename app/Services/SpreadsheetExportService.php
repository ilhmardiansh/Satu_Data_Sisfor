<?php

namespace App\Services;

use Illuminate\Http\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class SpreadsheetExportService
{
    public function safeFilename(string $title, string $extension): string
    {
        $clean = preg_replace('/[\\\\\/:*?"<>|]+/', '-', trim($title)) ?: 'dataset';

        return "{$clean}.{$extension}";
    }

    /**
     * Stream a UTF-8 BOM CSV file.
     *
     * @param  array<int, string>  $cols
     * @param  array<int, array<int, string|int|float|null>>  $rows
     */
    public function downloadCsv(string $title, array $cols, array $rows): StreamedResponse
    {
        $filename = $this->safeFilename($title, 'csv');

        return response()->streamDownload(function () use ($cols, $rows): void {
            $escape = static function (mixed $value): string {
                $str = (string) ($value ?? '');
                if (preg_match('/[",\n;]/', $str) === 1) {
                    return '"'.str_replace('"', '""', $str).'"';
                }

                return $str;
            };

            $lines = [implode(',', array_map($escape, $cols))];
            foreach ($rows as $row) {
                $lines[] = implode(',', array_map($escape, $row));
            }

            echo "\xEF\xBB\xBF".implode("\r\n", $lines);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Generate and return a native OOXML .xlsx spreadsheet using ZipArchive.
     *
     * @param  array<int, string>  $cols
     * @param  array<int, array<int, string|int|float|null>>  $rows
     */
    public function downloadXlsx(string $title, array $cols, array $rows): Response
    {
        $filename = $this->safeFilename($title, 'xlsx');
        $exportDir = storage_path('framework/exports');
        if (! is_dir($exportDir) && ! mkdir($exportDir, 0755, true) && ! is_dir($exportDir)) {
            throw new RuntimeException('Unable to prepare XLSX export directory.');
        }

        $tmpPath = tempnam($exportDir, 'sd_xlsx_');
        if ($tmpPath === false) {
            throw new RuntimeException('Unable to create temporary XLSX file.');
        }

        try {
            $zip = new ZipArchive;
            if ($zip->open($tmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to open XLSX archive.');
            }

            $escapeXml = static function (string $v): string {
                $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $v) ?? $v;

                return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            };

            $colName = static function (int $index): string {
                $name = '';
                $index++;
                while ($index > 0) {
                    $mod = ($index - 1) % 26;
                    $name = chr(65 + $mod).$name;
                    $index = intdiv($index - 1, 26);
                }

                return $name;
            };

            $sheetRowsXml = '';
            $allRows = array_merge([$cols], $rows);

            foreach ($allRows as $rIdx => $row) {
                $rowNumber = $rIdx + 1;
                $cellsXml = '';
                foreach (array_values($row) as $cIdx => $val) {
                    $ref = $colName((int) $cIdx).$rowNumber;
                    if ((is_int($val) || is_float($val)) && is_finite((float) $val)) {
                        $cellsXml .= "<c r=\"{$ref}\"><v>{$val}</v></c>";
                    } else {
                        $styleAttr = $rIdx === 0 ? ' s="1"' : '';
                        $cellsXml .= "<c r=\"{$ref}\" t=\"inlineStr\"{$styleAttr}><is><t xml:space=\"preserve\">".$escapeXml((string) ($val ?? '')).'</t></is></c>';
                    }
                }
                $sheetRowsXml .= "<row r=\"{$rowNumber}\">{$cellsXml}</row>";
            }

            $worksheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                ."<sheetData>{$sheetRowsXml}</sheetData>"
                .'</worksheet>';

            $zip->addFromString(
                '[Content_Types].xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>'
            );
            $zip->addFromString(
                '_rels/.rels',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>'
            );
            $zip->addFromString(
                'xl/workbook.xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Data" sheetId="1" r:id="rId1"/></sheets></workbook>'
            );
            $zip->addFromString(
                'xl/_rels/workbook.xml.rels',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>'
            );
            $zip->addFromString(
                'xl/styles.xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="2"><xf/><xf fontId="1" applyFont="1"/></cellXfs></styleSheet>'
            );
            $zip->addFromString('xl/worksheets/sheet1.xml', $worksheetXml);
            if ($zip->close() !== true) {
                throw new RuntimeException('Unable to finalize XLSX archive.');
            }

            $bytes = file_get_contents($tmpPath);
            if ($bytes === false) {
                throw new RuntimeException('Unable to read generated XLSX archive.');
            }
        } finally {
            @unlink($tmpPath);
        }

        $asciiFallback = preg_replace('/[^A-Za-z0-9._ -]+/', '-', $filename) ?: 'dataset.xlsx';
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename,
            $asciiFallback
        );

        return response($bytes, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => $disposition,
            'Content-Length' => (string) strlen($bytes),
        ]);
    }
}
