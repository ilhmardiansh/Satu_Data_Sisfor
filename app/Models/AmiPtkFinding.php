<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmiPtkFinding extends Model
{
    protected $fillable = [
        'jenis_ptk',
        'tahun_akademik',
        'no_temuan',
        'kriteria_atau_standar',
        'tanggal_temuan',
        'uraian',
        'kategori',
        'analisis_penyebab',
        'tindakan_koreksi',
        'rencana_selesai',
        'realisasi_selesai',
        'status',
        'link_bukti',
        'konfirmasi_auditor',
        'catatan',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
