<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionValue extends Model
{
    protected $table = 'data_isian';

    protected $fillable = [
        'id_pengisian',
        'id_kolom',
        'nilai',
        'nilai_mentah',
        'status_bersih',
        'jalur_berkas',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_bersih' => 'boolean',
        ];
    }

    public function pengisian(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'id_pengisian');
    }

    public function kolomFormulir(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'id_kolom');
    }

    /**
     * Update normalized value and mark field clean per DPPL bersihkanData().
     */
    public function bersihkanData(string $nilaiBaru): void
    {
        $this->update([
            'nilai' => $nilaiBaru,
            'status_bersih' => true,
        ]);
    }
}
