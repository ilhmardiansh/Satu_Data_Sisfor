<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataForm extends Model
{
    protected $table = 'formulir';

    protected $fillable = [
        'code',
        'id_kategori',
        'judul',
        'deskripsi',
        'tanggal_waktu',
        'status_aktif',
        'tahun',
        'periode',
        'target',
        'batasi_satu_respon',
        'pengaturan',
        'status_preprocessing',
        'ringkasan_preprocessing',
        'diproses_oleh',
        'waktu_diproses',
        'dibuat_oleh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'tanggal_waktu' => 'datetime',
            'status_aktif' => 'boolean',
            'batasi_satu_respon' => 'boolean',
            'pengaturan' => 'array',
            'ringkasan_preprocessing' => 'array',
            'waktu_diproses' => 'datetime',
        ];
    }

    /**
     * Default Google Forms Studio settings harmonized to UNUSA visual identity.
     *
     * @return array<string, mixed>
     */
    public static function defaultSettings(): array
    {
        return [
            'theme' => [
                'palette' => 'cerulean',
                'bg_tone' => 'soft',
                'header_preset' => 'cerulean-grid',
                'header_image' => '',
                'font_family' => 'jakarta',
                'header_size' => 24,
                'question_size' => 15,
                'text_size' => 13,
            ],
            'is_quiz' => false,
            'release_grade' => 'immediately',
            'quiz_show_missed' => true,
            'quiz_show_correct' => true,
            'quiz_show_points' => true,
            'default_points' => 10,
            'collect_identity' => true,
            'allow_edit_after_submit' => false,
            'closed_message' => 'Formulir ini sudah tidak menerima respons baru.',
            'show_progress_bar' => true,
            'shuffle_questions' => false,
            'confirmation_message' => 'Terima kasih, respons Anda telah tersimpan.',
            'show_another_response_link' => true,
            'show_summary_after_submit' => false,
            'default_required' => false,
        ];
    }

    protected function settings(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $defaults = self::defaultSettings();
                $stored = is_array($this->pengaturan) ? $this->pengaturan : [];
                $merged = array_merge($defaults, $stored);
                $merged['theme'] = array_merge(
                    $defaults['theme'],
                    is_array($stored['theme'] ?? null) ? $stored['theme'] : []
                );

                return $merged;
            },
        );
    }

    protected function categoryId(): Attribute
    {
        return Attribute::make(
            get: fn (): int => (int) $this->id_kategori,
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

    protected function deadline(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->tanggal_waktu,
        );
    }

    protected function createdBy(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->dibuat_oleh !== null ? (int) $this->dibuat_oleh : null,
        );
    }

    /**
     * Answerable input fields only (excludes section dividers and content/media blocks).
     */
    protected function fields(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $this->loadMissing('kolomFormulir');

                return $this->kolomFormulir
                    ->filter(fn (FormField $col): bool => $col->isInputField())
                    ->map(fn (FormField $col): array => $col->toFieldArray())
                    ->values()
                    ->all();
            },
        );
    }

    /**
     * All ordered form items (both input questions and layout/media/section blocks).
     */
    protected function items(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $this->loadMissing('kolomFormulir');

                return $this->kolomFormulir
                    ->map(fn (FormField $col): array => $col->toFieldArray())
                    ->values()
                    ->all();
            },
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'id_kategori');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function kolomFormulir(): HasMany
    {
        return $this->hasMany(FormField::class, 'id_formulir')
            ->orderBy('urutan')
            ->orderBy('id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'id_formulir');
    }

    public function datasets(): HasMany
    {
        return $this->hasMany(Dataset::class, 'id_formulir_sumber');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query
            ->where('status_aktif', true)
            ->where('tanggal_waktu', '>', now());
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where(function (Builder $sub): void {
            $sub->where('status_aktif', false)
                ->orWhere('tanggal_waktu', '<=', now());
        });
    }

    /**
     * Synchronize normalized kolom_formulir rows while preserving existing IDs when editing.
     *
     * @param  array<int, array<string, mixed>>  $fields
     */
    public function syncFields(array $fields): void
    {
        $existing = $this->kolomFormulir()->get()->keyBy('kode_kolom');
        $keptCodes = [];
        $usedCodes = [];

        foreach (array_values($fields) as $index => $item) {
            $rawCode = trim((string) ($item['id'] ?? '')) ?: 'q'.($index + 1);
            $code = $rawCode;
            $suffix = 2;
            while (isset($usedCodes[$code])) {
                $code = $rawCode.'_'.$suffix;
                $suffix++;
            }
            $usedCodes[$code] = true;

            $type = (string) ($item['type'] ?? 'text');
            if (! in_array($type, FormField::ALL_TYPES, true)) {
                $type = 'text';
            }

            $isInput = ! in_array($type, FormField::NON_INPUT_TYPES, true);
            $hasOptions = in_array($type, ['select', 'radio', 'checkbox'], true);
            $options = $hasOptions
                ? array_values(array_filter(array_map('trim', array_map('strval', $item['options'] ?? []))))
                : null;
            $helpText = trim((string) ($item['help'] ?? ''));

            $config = is_array($item['config'] ?? null) ? $item['config'] : [];
            if (mb_strlen($helpText) > 255) {
                $config['full_help'] = $helpText;
            } else {
                unset($config['full_help']);
            }

            $payload = [
                'id_formulir' => $this->id,
                'kode_kolom' => $code,
                'label' => trim((string) ($item['label'] ?? $code)),
                'deskripsi_bantuan' => $helpText !== '' ? mb_substr($helpText, 0, 255) : null,
                'tipe_kolom' => $type,
                'wajib_diisi' => $isInput ? (bool) ($item['required'] ?? false) : false,
                'opsi' => $options ?: null,
                'konfigurasi' => ! empty($config) ? $config : null,
                'urutan' => $index + 1,
            ];

            if ($existing->has($code)) {
                $existing->get($code)->update($payload);
            } else {
                FormField::create($payload);
            }

            $keptCodes[] = $code;
        }

        if (empty($keptCodes)) {
            $this->kolomFormulir()->delete();
        } else {
            $this->kolomFormulir()->whereNotIn('kode_kolom', $keptCodes)->delete();
        }

        $this->load('kolomFormulir');
    }

    public function tutupFormulir(): void
    {
        $this->update(['status_aktif' => false]);
    }

    public function isOpen(): bool
    {
        return (bool) $this->status_aktif
            && $this->tanggal_waktu !== null
            && $this->tanggal_waktu->isFuture();
    }

    public function isClosed(): bool
    {
        return ! $this->isOpen();
    }

    public function isPreprocessed(): bool
    {
        return $this->status_preprocessing === 'siap';
    }

    public function preprocessingStatusLabel(): string
    {
        return match ($this->status_preprocessing) {
            'siap' => 'Siap Dataset',
            'proses' => 'Perlu Pre-processing',
            default => 'Belum Diproses',
        };
    }

    public function canBeSeenBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin() || $user->isWali()) {
            return true;
        }

        return $this->target === 'Semua' || $this->target === $user->jenis;
    }

    public function isUrgent(): bool
    {
        if (! $this->isOpen()) {
            return false;
        }

        $seconds = now()->diffInSeconds($this->tanggal_waktu, false);

        return $seconds > 0 && $seconds < 3 * 86400;
    }

    public function timeLeftLabel(string $lang = 'id'): string
    {
        if (! $this->isOpen()) {
            return $lang === 'en' ? 'Closed' : 'Ditutup';
        }

        $seconds = now()->diffInSeconds($this->tanggal_waktu, false);

        if ($seconds <= 0) {
            return $lang === 'en' ? 'Closed' : 'Ditutup';
        }

        $days = intdiv((int) $seconds, 86400);

        if ($days >= 1) {
            return $lang === 'en' ? "Closes in {$days}d" : "Tutup dalam {$days} hari";
        }

        $hours = max(1, intdiv((int) $seconds, 3600));

        return $lang === 'en' ? "Closes in {$hours}h" : "Tutup dalam {$hours} jam";
    }

    public function deadlineFormatted(): string
    {
        if (! $this->tanggal_waktu) {
            return '-';
        }

        return $this->tanggal_waktu->copy()->locale('id')->translatedFormat('j M Y, H.i');
    }

    /**
     * @return array<int, array{id: string, label: string}>
     */
    public function sourceCols(): array
    {
        $cols = [
            ['id' => '_nama', 'label' => 'Nama pengirim'],
            ['id' => '_nim', 'label' => 'NIM/NIP'],
            ['id' => '_at', 'label' => 'Waktu kirim'],
        ];

        foreach ($this->fields ?? [] as $field) {
            $cols[] = [
                'id' => (string) ($field['id'] ?? ''),
                'label' => (string) ($field['label'] ?? ''),
            ];
        }

        return $cols;
    }
}
