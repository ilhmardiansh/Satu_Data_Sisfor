<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsArticle extends Model
{
    public const TAGS = [
        'Prestasi Mahasiswa',
        'Publikasi Dosen',
        'Pengabdian',
        'Akademik',
        'Kegiatan',
    ];

    protected $fillable = [
        'code',
        'badge',
        'corner',
        'date_label',
        'read_minutes',
        'title',
        'excerpt',
        'author',
        'author_initials',
        'image_url',
        'body',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_minutes' => 'integer',
            'body' => 'array',
        ];
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
