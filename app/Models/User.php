<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['code', 'nama_pengguna', 'nim', 'email', 'kata_sandi', 'id_peran', 'jenis', 'aktif'])]
#[Hidden(['kata_sandi', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'pengguna';

    /** @var array<int, string> */
    protected $with = ['peran.hakAkses'];

    public const ROLE_LABELS = [
        'produsen' => 'Produsen Data',
        'wali' => 'Wali Data',
        'admin' => 'Admin',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'aktif' => 'boolean',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'kata_sandi';
    }

    public function getAuthPassword(): string
    {
        return (string) $this->kata_sandi;
    }

    public function peran(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_peran');
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) $this->nama_pengguna,
        );
    }

    protected function role(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $this->loadMissing('peran');

                return (string) ($this->peran?->nama_peran ?? 'produsen');
            },
        );
    }

    protected function password(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) $this->kata_sandi,
        );
    }

    /**
     * Resolve or create a role primary key by nama_peran.
     */
    public static function resolveRoleId(string $roleName): int
    {
        $role = Role::query()->firstOrCreate(
            ['nama_peran' => $roleName],
            ['deskripsi' => self::ROLE_LABELS[$roleName] ?? ucfirst($roleName)]
        );

        return (int) $role->id;
    }

    public function scopeWithRole(Builder $query, string $roleName): Builder
    {
        return $query->whereHas('peran', function (Builder $sub) use ($roleName): void {
            $sub->where('nama_peran', $roleName);
        });
    }

    public function hasPermission(string $permissionName): bool
    {
        $this->loadMissing('peran.hakAkses');
        $peran = $this->peran;
        if (! $peran) {
            return false;
        }

        return $peran->hakAkses->contains('nama_hak_akses', $permissionName);
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
        return $this->hasMany(FormSubmission::class, 'diisi_oleh');
    }

    public function createdForms(): HasMany
    {
        return $this->hasMany(DataForm::class, 'dibuat_oleh');
    }

    public function createdDatasets(): HasMany
    {
        return $this->hasMany(Dataset::class, 'dibuat_oleh');
    }

    public function createdVisualizations(): HasMany
    {
        return $this->hasMany(Visualization::class, 'dibuat_oleh');
    }

    public function dataRequests(): HasMany
    {
        return $this->hasMany(DataRequest::class, 'diajukan_oleh');
    }
}
