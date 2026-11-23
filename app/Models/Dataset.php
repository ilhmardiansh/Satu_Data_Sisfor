<?php

namespace App\Models;

use App\Services\ChartPaletteService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dataset extends Model
{
    protected $fillable = [
        'code',
        'category_id',
        'source_form_id',
        'title',
        'description',
        'tahun',
        'periode',
        'access',
        'format',
        'updated_label',
        'cols',
        'rows',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'cols' => 'array',
            'rows' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sourceForm(): BelongsTo
    {
        return $this->belongsTo(DataForm::class, 'source_form_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(DataRequest::class);
    }

    public function visualizations(): HasMany
    {
        return $this->hasMany(Visualization::class);
    }

    /**
     * @return array<int, string>
     */
    public function numericColumns(): array
    {
        return ChartPaletteService::numericColumns($this->cols ?? [], $this->rows ?? []);
    }

    /**
     * @param  array<int, string>  $sessionRequestCodes
     */
    public function isAccessibleBy(?User $user, ?string $requesterToken = null, array $sessionRequestCodes = []): bool
    {
        if ($this->access === 'public') {
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
                    $query->orWhere('user_id', $user->id);
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
