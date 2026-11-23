<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['code', 'name', 'nim', 'email', 'password', 'role', 'jenis', 'aktif'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_LABELS = [
        'produsen' => 'Produsen Data',
        'wali' => 'Wali Data',
        'admin' => 'Admin',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'aktif' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isWali(): bool
    {
        return $this->role === 'wali';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['admin', 'wali'], true);
    }

    public function isProdusen(): bool
    {
        return $this->role === 'produsen';
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? ucfirst($this->role);
    }

    public function initials(): string
    {
        $clean = preg_replace('/^(Dr|Prof)\.?\s*/i', '', trim($this->name)) ?? $this->name;
        $parts = preg_split('/[\s,]+/', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $letters = [];
        foreach (array_slice($parts, 0, 2) as $part) {
            $letters[] = mb_strtoupper(mb_substr($part, 0, 1));
        }

        return implode('', $letters) ?: 'U';
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'user_id');
    }

    public function createdForms(): HasMany
    {
        return $this->hasMany(DataForm::class, 'created_by');
    }

    public function createdDatasets(): HasMany
    {
        return $this->hasMany(Dataset::class, 'created_by');
    }

    public function createdVisualizations(): HasMany
    {
        return $this->hasMany(Visualization::class, 'created_by');
    }

    public function dataRequests(): HasMany
    {
        return $this->hasMany(DataRequest::class, 'user_id');
    }
}
