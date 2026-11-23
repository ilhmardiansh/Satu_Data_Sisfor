<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataRequest extends Model
{
    public const PURPOSES = [
        'Penelitian / tugas akhir',
        'Akreditasi & penjaminan mutu',
        'Pelaporan instansi',
        'Publikasi / jurnalistik',
        'Perencanaan program',
        'Lainnya',
    ];

    protected $fillable = [
        'code',
        'dataset_id',
        'user_id',
        'session_token',
        'nama',
        'email',
        'hp',
        'nim',
        'tujuan',
        'deskripsi',
        'status',
        'note',
        'decided_by',
        'decided_at',
        'requested_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
