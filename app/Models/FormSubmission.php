<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmission extends Model
{
    protected $table = 'pengisian_formulir';

    protected $fillable = [
        'code',
        'id_formulir',
        'diisi_oleh',
        'waktu_pengisian',
        'status_validasi',
        'catatan_validasi',
        'sumber_input',
        'jalur_berkas_bukti',
        'dibersihkan_oleh',
        'waktu_dibersihkan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'waktu_pengisian' => 'datetime',
            'waktu_dibersihkan' => 'datetime',
        ];
    }

    protected function dataFormId(): Attribute
    {
        return Attribute::make(
            get: fn (): int => (int) $this->id_formulir,
        );
    }

    protected function userId(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->diisi_oleh !== null ? (int) $this->diisi_oleh : null,
        );
    }

    protected function submittedAt(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->waktu_pengisian,
        );
    }

    protected function cleanedBy(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->dibersihkan_oleh !== null ? (int) $this->dibersihkan_oleh : null,
        );
    }

    protected function cleanedAt(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->waktu_dibersihkan,
        );
    }

    protected function values(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $this->loadMissing('dataIsian.kolomFormulir');
                $out = [];
                foreach ($this->dataIsian as $item) {
                    $code = $item->kolomFormulir?->kode_kolom;
                    if ($code !== null && $code !== '') {
                        $out[$code] = (string) ($item->nilai ?? '');
                    }
                }

                return $out;
            },
        );
    }

    protected function rawValues(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $this->loadMissing('dataIsian.kolomFormulir');
                $out = [];
                foreach ($this->dataIsian as $item) {
                    $code = $item->kolomFormulir?->kode_kolom;
                    if ($code !== null && $code !== '') {
                        $out[$code] = (string) ($item->nilai_mentah ?? $item->nilai ?? '');
                    }
                }

                return $out;
            },
        );
    }

    protected function attachments(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $this->loadMissing('dataIsian.kolomFormulir');
                $out = [];
                foreach ($this->dataIsian as $item) {
                    $code = $item->kolomFormulir?->kode_kolom;
                    if ($code !== null && $code !== '' && is_string($item->jalur_berkas) && $item->jalur_berkas !== '') {
                        $out[$code] = $item->jalur_berkas;
                    }
                }

                return $out;
            },
        );
    }

    public function dataForm(): BelongsTo
    {
        return $this->belongsTo(DataForm::class, 'id_formulir');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diisi_oleh');
    }

    public function cleaner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibersihkan_oleh');
    }

    public function dataIsian(): HasMany
    {
        return $this->hasMany(SubmissionValue::class, 'id_pengisian');
    }

    /**
     * Synchronize normalized data_isian rows for each kolom_formulir of the form.
     *
     * @param  array<string, mixed>  $values
     * @param  array<string, string>|null  $attachments
     * @param  array<string, bool>|null  $cleanStatusMap
     */
    public function syncValues(
        DataForm $form,
        array $values,
        ?array $attachments = null,
        ?array $cleanStatusMap = null,
        bool $preserveRaw = false
    ): void {
        $form->loadMissing('kolomFormulir');
        $existing = $this->dataIsian()->get()->keyBy('id_kolom');
        $firstEvidencePath = null;

        $inputCols = $form->kolomFormulir->filter(
            fn (FormField $col): bool => $col->isInputField()
        );

        foreach ($inputCols as $col) {
            $kode = $col->kode_kolom;
            $val = array_key_exists($kode, $values) ? (string) ($values[$kode] ?? '') : '';
            $existingRow = $existing->get($col->id);
            $path = $attachments !== null
                ? (array_key_exists($kode, $attachments) ? ($attachments[$kode] ?: null) : $existingRow?->jalur_berkas)
                : $existingRow?->jalur_berkas;

            if ($firstEvidencePath === null && is_string($path) && $path !== '') {
                $firstEvidencePath = $path;
            }

            $isClean = $cleanStatusMap !== null
                ? (bool) ($cleanStatusMap[$kode] ?? true)
                : true;

            $payload = [
                'nilai' => $val,
                'jalur_berkas' => $path,
                'status_bersih' => $isClean,
            ];

            if (! ($preserveRaw && $existingRow && $existingRow->nilai_mentah !== null)) {
                $payload['nilai_mentah'] = $val;
            }

            SubmissionValue::query()->updateOrCreate(
                [
                    'id_pengisian' => $this->id,
                    'id_kolom' => $col->id,
                ],
                $payload
            );
        }

        if ($attachments !== null && $this->jalur_berkas_bukti !== $firstEvidencePath) {
            $this->forceFill(['jalur_berkas_bukti' => $firstEvidencePath])->save();
        } elseif ($firstEvidencePath !== null && $this->jalur_berkas_bukti !== $firstEvidencePath) {
            $this->forceFill(['jalur_berkas_bukti' => $firstEvidencePath])->save();
        }

        $this->load('dataIsian.kolomFormulir');
    }

    public function validationLabel(): string
    {
        return match ($this->status_validasi) {
            'valid' => 'Valid · Siap Dataset',
            'ditolak' => 'Ditolak',
            default => 'Menunggu Pre-processing',
        };
    }

    public function submittedFormatted(): string
    {
        if (! $this->waktu_pengisian) {
            return '-';
        }

        return $this->waktu_pengisian->copy()->locale('id')->translatedFormat('j M Y, H.i');
    }
}
