<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmiRecommendation extends Model
{
    protected $fillable = [
        'no_urut',
        'aspek_bidang',
        'kelebihan',
        'peluang_peningkatan',
        'tanggal_audit',
        'ketua_spmi',
        'wakil_auditee',
        'auditor_ketua',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'no_urut' => 'integer',
        ];
    }
}
