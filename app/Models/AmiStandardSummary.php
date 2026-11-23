<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmiStandardSummary extends Model
{
    protected $fillable = [
        'urutan',
        'standar_nomor',
        'standar_nama',
        'kelompok_standar',
        'jumlah_indikator',
        'jumlah_tercapai',
        'jumlah_tidak_tercapai',
        'persentase_tercapai',
        'rata_rata_capaian',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'standar_nomor' => 'integer',
            'jumlah_indikator' => 'integer',
            'jumlah_tercapai' => 'integer',
            'jumlah_tidak_tercapai' => 'integer',
            'persentase_tercapai' => 'float',
            'rata_rata_capaian' => 'float',
        ];
    }
}
