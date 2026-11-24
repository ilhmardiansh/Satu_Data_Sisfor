<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramProfile extends Model
{
    protected $table = 'profil_prodi';

    protected $fillable = [
        'sejarah',
        'visi_misi',
        'deskripsi_prodi',
        'gelar',
        'durasi_studi',
        'persyaratan',
        'lokasi_kampus',
        'fakultas',
        'fasilitas',
        'kontak',
        'nama_prodi',
        'akreditasi',
        'tahun_berdiri',
        'dikelola_oleh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visi_misi' => 'array',
            'kontak' => 'array',
        ];
    }

    protected function deskripsi(): Attribute
    {
        return Attribute::make(get: fn (): string => (string) $this->deskripsi_prodi);
    }

    protected function visi(): Attribute
    {
        return Attribute::make(get: fn (): string => (string) ($this->visi_misi['visi'] ?? ''));
    }

    protected function misi(): Attribute
    {
        return Attribute::make(get: fn (): array => array_values($this->visi_misi['misi'] ?? []));
    }

    protected function email(): Attribute
    {
        return Attribute::make(get: fn (): string => (string) ($this->kontak['email'] ?? ''));
    }

    protected function telp(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->kontak['telp'] ?? null);
    }

    protected function alamat(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->lokasi_kampus);
    }

    protected function program(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->nama_prodi);
    }

    protected function durasi(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->durasi_studi);
    }

    protected function syarat(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->persyaratan);
    }

    protected function berdiri(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->tahun_berdiri);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikelola_oleh');
    }

    /**
     * Update program profile per DPPL perbaruiProfil().
     *
     * @param  array<string, mixed>  $data
     */
    public function perbaruiProfil(array $data): void
    {
        $this->update($data);
    }
}
