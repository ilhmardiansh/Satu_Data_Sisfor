<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'code',
        'tahun',
        'semester',
        'kode',
        'nama',
        'sks',
        'pilihan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'semester' => 'integer',
            'sks' => 'integer',
            'pilihan' => 'boolean',
        ];
    }
}
