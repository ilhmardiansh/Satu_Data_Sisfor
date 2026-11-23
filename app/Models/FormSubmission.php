<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    protected $fillable = [
        'code',
        'data_form_id',
        'user_id',
        'values',
        'attachments',
        'submitted_at',
        'cleaned_by',
        'cleaned_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'values' => 'array',
            'attachments' => 'array',
            'submitted_at' => 'datetime',
            'cleaned_at' => 'datetime',
        ];
    }

    public function dataForm(): BelongsTo
    {
        return $this->belongsTo(DataForm::class, 'data_form_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cleaner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cleaned_by');
    }

    public function submittedFormatted(): string
    {
        if (! $this->submitted_at) {
            return '-';
        }

        return $this->submitted_at->copy()->locale('id')->translatedFormat('j M Y, H.i');
    }
}
