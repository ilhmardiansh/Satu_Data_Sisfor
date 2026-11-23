<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramProfile extends Model
{
    protected $fillable = [
        'deskripsi',
        'visi',
        'misi',
        'akreditasi',
        'berdiri',
        'alamat',
        'email',
        'telp',
        'sejarah',
        'program',
        'gelar',
        'durasi',
        'syarat',
        'fakultas',
        'fasilitas',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'misi' => 'array',
        ];
    }
}
