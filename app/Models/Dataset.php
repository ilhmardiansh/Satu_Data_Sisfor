<?php

namespace App\Models;

use App\Services\ChartPaletteService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dataset extends Model
{
    protected $table = 'dataset';

    protected $fillable = [
        'code',
        'id_kategori',
        'id_formulir_sumber',
        'judul',
        'deskripsi',
        'tahun',
        'tipe_akses',
        'status_publikasi',
        'periode',
        'format',
        'label_diperbarui',
        'kolom',
        'baris',
        'konfigurasi_sumber',
        'dibuat_oleh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'string',
            'kolom' => 'array',
            'baris' => 'array',
            'konfigurasi_sumber' => 'array',
        ];
    }

    protected function categoryId(): Attribute
    {
        return Attribute::make(
            get: fn (): int => (int) $this->id_kategori,
        );
    }

    protected function sourceFormId(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->id_formulir_sumber !== null ? (int) $this->id_formulir_sumber : null,
        );
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) $this->judul,
        );
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->deskripsi,
        );
    }

    protected function access(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) $this->tipe_akses,
        );
    }

    protected function updatedLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->label_diperbarui,
        );
    }

    protected function cols(): Attribute
    {
        return Attribute::make(
            get: fn (): array => $this->kolom ?? [],
        );
    }

    protected function rows(): Attribute
    {
        return Attribute::make(
            get: fn (): array => $this->baris ?? [],
        );
    }

    protected function createdBy(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->dibuat_oleh !== null ? (int) $this->dibuat_oleh : null,
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'id_kategori');
    }

    public function sourceForm(): BelongsTo
    {
        return $this->belongsTo(DataForm::class, 'id_formulir_sumber');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(DataRequest::class, 'id_dataset');
    }

    public function visualizations(): HasMany
    {
        return $this->hasMany(Visualization::class, 'id_dataset');
    }

    /**
     * @return array<int, string>
     */
    public function numericColumns(): array
    {
        return ChartPaletteService::numericColumns($this->kolom ?? [], $this->baris ?? []);
    }

    public function scopePublished(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status_publikasi', 'terpublikasi');
    }

    public function isPublished(): bool
    {
        return ($this->status_publikasi ?? 'terpublikasi') === 'terpublikasi';
    }

    /**
     * @param  array<int, string>  $sessionRequestCodes
     */
    public function isAccessibleBy(?User $user, ?string $requesterToken = null, array $sessionRequestCodes = []): bool
    {
        if (! $this->isPublished()) {
            return $user !== null && $user->isStaff();
        }

        if ($this->tipe_akses === 'public') {
            return true;
        }

        if ($user && $user->isStaff()) {
            return true;
        }

        if (! $user && ! $requesterToken && empty($sessionRequestCodes)) {
            return false;
        }

        return $this->requests()
            ->where('status', 'disetujui')
            ->where(function ($query) use ($user, $requesterToken, $sessionRequestCodes): void {
                if ($user) {
                    $query->orWhere('diajukan_oleh', $user->id);
                }
                if ($requesterToken) {
                    $query->orWhere('session_token', $requesterToken);
                }
                if (! empty($sessionRequestCodes)) {
                    $query->orWhereIn('code', $sessionRequestCodes);
                }
            })
            ->exists();
    }
}
