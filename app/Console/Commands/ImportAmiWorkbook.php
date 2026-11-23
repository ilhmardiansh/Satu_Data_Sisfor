<?php

namespace App\Console\Commands;

use App\Services\AmiWorkbookImporter;
use Illuminate\Console\Command;

class ImportAmiWorkbook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ami:import
                            {path? : Path ke berkas Excel AMI (.xlsx)}
                            {--export-json : Ekspor hasil parsing ke JSON snapshot agar seeding cepat}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Impor seluruh data AMI SNPT-U (28 & 38 Standar, Benchmark, PTK, Saran) beserta datasetnya ke database.';

    public function handle(AmiWorkbookImporter $importer): int
    {
        $argPath = (string) ($this->argument('path') ?? '');
        $xlsxPath = $argPath !== '' ? $argPath : base_path(AmiWorkbookImporter::DEFAULT_WORKBOOK_PATH);
        $snapshotPath = base_path(AmiWorkbookImporter::DEFAULT_SNAPSHOT_PATH);

        if (! is_file($xlsxPath)) {
            $this->error("Berkas Excel tidak ditemukan: {$xlsxPath}");

            return self::FAILURE;
        }

        if ($this->option('export-json')) {
            $exported = $importer->exportParsedJson($xlsxPath, $snapshotPath);
            $this->info('Snapshot JSON AMI berhasil diekspor: '.json_encode($exported, JSON_UNESCAPED_UNICODE));
        }

        $stats = $importer->importIntoDatabase($xlsxPath, null, null);

        $this->table(
            ['Entitas', 'Jumlah Diimpor'],
            [
                ['Indikator AMI 28 Standar', $stats['indicators']],
                ['Rekap 28 Standar', $stats['standard_summaries']],
                ['Benchmark 38 Standar (16 Prodi)', $stats['benchmarks']],
                ['Temuan PTK (2018–2026)', $stats['ptk_findings']],
                ['Saran Pengembangan', $stats['recommendations']],
                ['Dataset Katalog AMI (DS-13..DS-22)', $stats['datasets']],
                ['Visualisasi Dashboard (V-04..V-05)', $stats['visualizations']],
            ]
        );

        return self::SUCCESS;
    }
}
