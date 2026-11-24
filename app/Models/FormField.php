<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormField extends Model
{
    protected $table = 'kolom_formulir';

    protected $fillable = [
        'id_formulir',
        'kode_kolom',
        'label',
        'deskripsi_bantuan',
        'tipe_kolom',
        'wajib_diisi',
        'opsi',
        'urutan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'wajib_diisi' => 'boolean',
            'opsi' => 'array',
            'urutan' => 'integer',
        ];
    }

    public function formulir(): BelongsTo
    {
        return $this->belongsTo(DataForm::class, 'id_formulir');
    }

    public function dataIsian(): HasMany
    {
        return $this->hasMany(SubmissionValue::class, 'id_kolom');
    }

    /**
     * Format field definition for UI builders and validation services.
     *
     * @return array{id: string, label: string, help: string, type: string, required: bool, options?: array<int, string>}
     */
    public function toFieldArray(): array
    {
        $item = [
            'id' => (string) $this->kode_kolom,
            'label' => (string) $this->label,
            'help' => (string) ($this->deskripsi_bantuan ?? ''),
            'type' => (string) $this->tipe_kolom,
            'required' => (bool) $this->wajib_diisi,
        ];

        if (in_array($this->tipe_kolom, ['select', 'radio', 'checkbox'], true) || ! empty($this->opsi)) {
            $item['options'] = array_values(array_map('strval', $this->opsi ?? []));
        }

        return $item;
    }
}
