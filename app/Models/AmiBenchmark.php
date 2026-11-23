<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmiBenchmark extends Model
{
    protected $fillable = [
        'standar_nomor',
        'standar_nama',
        'sub_kelompok',
        'no_butir',
        'indikator',
        'prodi_values',
        'nilai_si',
        'rekap_unusa',
        'baseline_2023',
        'target_2024',
        'asal_data_2023',
        'asal_data_2024',
        'catatan_review_prodi',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'standar_nomor' => 'integer',
            'prodi_values' => 'array',
            'sort_order' => 'integer',
        ];
    }
}
