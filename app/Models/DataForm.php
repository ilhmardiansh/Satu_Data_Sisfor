<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataForm extends Model
{
    protected $fillable = [
        'code',
        'category_id',
        'title',
        'description',
        'tahun',
        'periode',
        'target',
        'deadline',
        'fields',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'deadline' => 'datetime',
            'fields' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'data_form_id');
    }

    public function datasets(): HasMany
    {
        return $this->hasMany(Dataset::class, 'source_form_id');
    }

    public function isOpen(): bool
    {
        return $this->deadline !== null && $this->deadline->isFuture();
    }

    public function canBeSeenBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->role !== 'produsen') {
            return true;
        }

        return $this->target === 'Semua' || $this->target === $user->jenis;
    }

    public function isUrgent(): bool
    {
        if (! $this->isOpen()) {
            return false;
        }

        $seconds = now()->diffInSeconds($this->deadline, false);

        return $seconds > 0 && $seconds < 3 * 86400;
    }

    public function timeLeftLabel(string $lang = 'id'): string
    {
        if (! $this->deadline) {
            return $lang === 'en' ? 'Closed' : 'Ditutup';
        }

        $seconds = now()->diffInSeconds($this->deadline, false);

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
        if (! $this->deadline) {
            return '-';
        }

        return $this->deadline->copy()->locale('id')->translatedFormat('j M Y, H.i');
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
