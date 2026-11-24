<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lecturer extends Model
{
    protected $table = 'dosen';

    public const JABATAN_OPTIONS = [
        'Tenaga Pengajar',
        'Asisten Ahli',
        'Lektor',
        'Lektor Kepala',
        'Profesor',
    ];

    public const PENDIDIKAN_OPTIONS = [
        'S2',
        'S3',
    ];

    protected $fillable = [
        'code',
        'nama',
        'gelar',
        'jalur_foto',
        'nidn',
        'jabatan',
        'pendidikan',
        'bidang',
        'email',
        'mata_kuliah',
        'profil',
        'urutan',
        'dikelola_oleh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    protected function foto(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->jalur_foto);
    }

    protected function sortOrder(): Attribute
    {
        return Attribute::make(get: fn (): int => (int) $this->urutan);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikelola_oleh');
    }

    public function initials(): string
    {
        $clean = preg_replace('/^(Dr|Prof)\.?\s*/i', '', trim($this->nama)) ?? $this->nama;
        $parts = preg_split('/[\s,]+/', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $letters = [];
        foreach ($parts as $part) {
            if (preg_match('/^[A-Z]/', $part) === 1) {
                $letters[] = mb_substr($part, 0, 1);
                if (count($letters) >= 2) {
                    break;
                }
            }
        }

        return implode('', $letters) ?: mb_strtoupper(mb_substr($this->nama, 0, 2));
    }

    /**
     * @return array<int, string>
     */
    public function mataKuliahList(): array
    {
        if (! $this->mata_kuliah) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $this->mata_kuliah))));
    }
}
