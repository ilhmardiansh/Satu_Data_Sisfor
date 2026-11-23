<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    public const TINGKAT_OPTIONS = [
        'Regional',
        'Nasional',
        'Internasional',
    ];

    protected $fillable = [
        'code',
        'judul',
        'nama',
        'tingkat',
        'peringkat',
        'tahun',
        'foto',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
        ];
    }
}
