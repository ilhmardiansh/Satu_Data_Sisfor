<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormField extends Model
{
    public const NON_INPUT_TYPES = [
        'section',
        'title_block',
        'image_block',
        'video_block',
    ];

    public const INPUT_TYPES = [
        'text',
        'textarea',
        'number',
        'radio',
        'checkbox',
        'select',
        'file',
        'linear_scale',
        'rating',
        'mc_grid',
        'checkbox_grid',
        'date',
        'time',
    ];

    public const ALL_TYPES = [
        ...self::INPUT_TYPES,
        ...self::NON_INPUT_TYPES,
    ];

    protected $table = 'kolom_formulir';

    protected $fillable = [
        'id_formulir',
        'kode_kolom',
        'label',
        'deskripsi_bantuan',
        'tipe_kolom',
        'wajib_diisi',
        'opsi',
        'konfigurasi',
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
            'konfigurasi' => 'array',
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

    public function isInputField(): bool
    {
        return ! in_array($this->tipe_kolom, self::NON_INPUT_TYPES, true);
    }

    /**
     * Format field or structural block definition for UI builders, respondent renderer, and validation services.
     *
     * @return array{
     *     id: string,
     *     label: string,
     *     help: string,
     *     type: string,
     *     required: bool,
     *     options?: array<int, string>,
     *     config: array<string, mixed>
     * }
     */
    public function toFieldArray(): array
    {
        $config = is_array($this->konfigurasi) ? $this->konfigurasi : [];
        $help = is_string($config['full_help'] ?? null) && $config['full_help'] !== ''
            ? $config['full_help']
            : (string) ($this->deskripsi_bantuan ?? '');

        unset($config['full_help']);

        $item = [
            'id' => (string) $this->kode_kolom,
            'label' => (string) $this->label,
            'help' => $help,
            'type' => (string) $this->tipe_kolom,
            'required' => $this->isInputField() ? (bool) $this->wajib_diisi : false,
            'config' => $config,
        ];

        if (in_array($this->tipe_kolom, ['select', 'radio', 'checkbox'], true) || ! empty($this->opsi)) {
            $item['options'] = array_values(array_map('strval', $this->opsi ?? []));
        }

        return $item;
    }
}
