<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
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
        'nidn',
        'jabatan',
        'pendidikan',
        'bidang',
        'foto',
        'email',
        'mata_kuliah',
        'profil',
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
