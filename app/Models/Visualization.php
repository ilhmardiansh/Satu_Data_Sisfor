<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visualization extends Model
{
    protected $table = 'visualisasi_dashboard';

    protected $fillable = [
        'code',
        'id_dataset',
        'judul',
        'tipe_grafik',
        'konfigurasi_query',
        'dibuat_oleh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'konfigurasi_query' => 'array',
        ];
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) $this->judul,
        );
    }

    protected function datasetId(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->id_dataset !== null ? (int) $this->id_dataset : null,
        );
    }

    protected function kind(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) $this->tipe_grafik,
        );
    }

    protected function createdBy(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->dibuat_oleh !== null ? (int) $this->dibuat_oleh : null,
        );
    }

    protected function labelCol(): Attribute
    {
        return Attribute::make(
            get: fn (): int => (int) ($this->konfigurasi_query['label_col'] ?? 0),
        );
    }

    protected function valueCol(): Attribute
    {
        return Attribute::make(
            get: fn (): int => (int) ($this->konfigurasi_query['value_col'] ?? 1),
        );
    }

    protected function color(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) ($this->konfigurasi_query['color'] ?? 'sky'),
        );
    }

    protected function show(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => (bool) ($this->konfigurasi_query['show'] ?? true),
        );
    }

    public function scopeDatasetCharts(Builder $query): Builder
    {
        return $query->whereNotNull('id_dataset');
    }

    public function scopeMetricCards(Builder $query): Builder
    {
        return $query->whereNull('id_dataset');
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class, 'id_dataset');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
