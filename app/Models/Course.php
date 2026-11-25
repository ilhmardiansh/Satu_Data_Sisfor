<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Course extends Model
{
    protected $table = 'kurikulum';

    protected $fillable = [
        'code',
        'kode_mk',
        'nama_mk',
        'sks',
        'semester',
        'tahun',
        'pilihan',
        'dikelola_oleh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'string',
            'semester' => 'integer',
            'sks' => 'integer',
            'pilihan' => 'boolean',
        ];
    }

    protected function kode(): Attribute
    {
        return Attribute::make(get: fn (): string => (string) $this->kode_mk);
    }

    protected function nama(): Attribute
    {
        return Attribute::make(get: fn (): string => (string) $this->nama_mk);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikelola_oleh');
    }
}
