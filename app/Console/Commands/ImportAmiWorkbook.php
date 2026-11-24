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
    protected $description = 'Impor data Excel AMI SNPT-U ke formulir, pengisian_formulir, data_isian, dataset, dan visualisasi DPPL.';

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
            ['Entitas DPPL', 'Jumlah Diimpor'],
            [
                ['Formulir AMI (F-05..F-08)', $stats['forms']],
                ['Pengisian Indikator 28 Standar (F-06)', $stats['indicators_submissions']],
                ['Pengisian Temuan PTK (F-07)', $stats['ptk_submissions']],
                ['Pengisian Saran Pengembangan (F-08)', $stats['recommendation_submissions']],
                ['Total Baris Data Isian (data_isian)', $stats['data_isian']],
                ['Dataset Katalog AMI (DS-13..DS-22)', $stats['datasets']],
                ['Visualisasi Dashboard (V-04..V-05)', $stats['visualizations']],
            ]
        );

        return self::SUCCESS;
    }
}
