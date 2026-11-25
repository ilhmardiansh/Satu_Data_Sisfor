<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'kategori';

    protected $fillable = [
        'code',
        'nama_kategori',
        'deskripsi',
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) $this->nama_kategori,
        );
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->deskripsi,
        );
    }

    public function dataForms(): HasMany
    {
        return $this->hasMany(DataForm::class, 'id_kategori');
    }

    public function datasets(): HasMany
    {
        return $this->hasMany(Dataset::class, 'id_kategori');
    }
}
