<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataRequest extends Model
{
    protected $table = 'permohonan_data';

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
        'id_dataset',
        'diajukan_oleh',
        'session_token',
        'nama_pemohon',
        'email_pemohon',
        'hp_pemohon',
        'nim_pemohon',
        'tujuan',
        'alasan',
        'status',
        'catatan_konfirmasi',
        'dikonfirmasi_oleh',
        'waktu_pengajuan',
        'waktu_konfirmasi',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'waktu_pengajuan' => 'datetime',
            'waktu_konfirmasi' => 'datetime',
        ];
    }

    protected function datasetId(): Attribute
    {
        return Attribute::make(get: fn (): int => (int) $this->id_dataset);
    }

    protected function userId(): Attribute
    {
        return Attribute::make(get: fn (): ?int => $this->diajukan_oleh !== null ? (int) $this->diajukan_oleh : null);
    }

    protected function nama(): Attribute
    {
        return Attribute::make(get: fn (): string => (string) $this->nama_pemohon);
    }

    protected function email(): Attribute
    {
        return Attribute::make(get: fn (): string => (string) $this->email_pemohon);
    }

    protected function hp(): Attribute
    {
        return Attribute::make(get: fn (): string => (string) $this->hp_pemohon);
    }

    protected function nim(): Attribute
    {
        return Attribute::make(get: fn (): string => (string) $this->nim_pemohon);
    }

    protected function deskripsi(): Attribute
    {
        return Attribute::make(get: fn (): string => (string) $this->alasan);
    }

    protected function note(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->catatan_konfirmasi);
    }

    protected function decidedBy(): Attribute
    {
        return Attribute::make(get: fn (): ?int => $this->dikonfirmasi_oleh !== null ? (int) $this->dikonfirmasi_oleh : null);
    }

    protected function requestedAt(): Attribute
    {
        return Attribute::make(get: fn () => $this->waktu_pengajuan);
    }

    protected function decidedAt(): Attribute
    {
        return Attribute::make(get: fn () => $this->waktu_konfirmasi);
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class, 'id_dataset');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_oleh');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikonfirmasi_oleh');
    }

    /**
     * Confirm approval or rejection per DPPL konfirmasiPermohonan().
     */
    public function konfirmasiPermohonan(string $status, ?string $catatan, int $deciderId): void
    {
        $cleanNote = trim((string) ($catatan ?? ''));

        $this->update([
            'status' => $status,
            'catatan_konfirmasi' => $cleanNote !== '' ? $cleanNote : null,
            'dikonfirmasi_oleh' => $deciderId,
            'waktu_konfirmasi' => now(),
        ]);
    }
}
