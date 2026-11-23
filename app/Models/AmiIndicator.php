<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmiIndicator extends Model
{
    protected $fillable = [
        'code',
        'kelompok_standar',
        'standar_nomor',
        'standar_nama',
        'no_butir',
        'indikator',
        'baseline_2023',
        'baseline_2024',
        'keterangan_umum',
        'keterangan_renstra',
        'pengukuran',
        'target_universitas',
        'target_renop_prodi',
        'target_akhir',
        'catatan_perhitungan',
        'realisasi',
        'link_bukti',
        'konfirmasi_auditor',
        'catatan_auditor',
        'status',
        'capaian',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'standar_nomor' => 'integer',
            'capaian' => 'float',
            'sort_order' => 'integer',
        ];
    }
}
