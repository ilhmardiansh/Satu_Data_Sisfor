<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsArticle extends Model
{
    protected $table = 'berita';

    public const TAGS = [
        'Prestasi Mahasiswa',
        'Publikasi Dosen',
        'Pengabdian',
        'Akademik',
        'Kegiatan',
    ];

    protected $fillable = [
        'code',
        'judul',
        'isi_berita',
        'tanggal_publikasi',
        'penulis',
        'kategori_badge',
        'label_pojok',
        'label_tanggal',
        'menit_baca',
        'ringkasan',
        'inisial_penulis',
        'jalur_gambar',
        'dikelola_oleh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'isi_berita' => 'array',
            'tanggal_publikasi' => 'date',
            'menit_baca' => 'integer',
        ];
    }

    protected function title(): Attribute
    {
        return Attribute::make(get: fn (): string => (string) $this->judul);
    }

    protected function body(): Attribute
    {
        return Attribute::make(get: fn (): array => $this->isi_berita ?? []);
    }

    protected function author(): Attribute
    {
        return Attribute::make(get: fn (): string => (string) $this->penulis);
    }

    protected function badge(): Attribute
    {
        return Attribute::make(get: fn (): string => (string) $this->kategori_badge);
    }

    protected function corner(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->label_pojok);
    }

    protected function dateLabel(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->label_tanggal);
    }

    protected function readMinutes(): Attribute
    {
        return Attribute::make(get: fn (): int => (int) $this->menit_baca);
    }

    protected function excerpt(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->ringkasan);
    }

    protected function authorInitials(): Attribute
    {
        return Attribute::make(get: fn (): string => (string) $this->inisial_penulis);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->jalur_gambar);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikelola_oleh');
    }

    public static function makeInitials(string $author): string
    {
        $parts = preg_split('/\s+/', trim($author), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $letters = [];
        foreach (array_slice($parts, 0, 2) as $part) {
            $letters[] = mb_strtoupper(mb_substr($part, 0, 1));
        }

        return implode('', $letters) ?: 'SI';
    }
}
