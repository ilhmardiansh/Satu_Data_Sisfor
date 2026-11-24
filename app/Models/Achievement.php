<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Achievement extends Model
{
    protected $table = 'prestasi';

    public const TINGKAT_OPTIONS = [
        'Regional',
        'Nasional',
        'Internasional',
    ];

    protected $fillable = [
        'code',
        'judul',
        'deskripsi',
        'tanggal',
        'jalur_gambar',
        'nama_peraih',
        'tingkat',
        'peringkat',
        'tahun',
        'dikelola_oleh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tahun' => 'integer',
        ];
    }

    protected function nama(): Attribute
    {
        return Attribute::make(get: fn (): string => (string) $this->nama_peraih);
    }

    protected function foto(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->jalur_gambar);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikelola_oleh');
    }
}
