<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $table = 'peran';

    protected $fillable = [
        'nama_peran',
        'deskripsi',
    ];

    public function hakAkses(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'peran_hak_akses',
            'id_peran',
            'id_hak_akses'
        );
    }

    public function pengguna(): HasMany
    {
        return $this->hasMany(User::class, 'id_peran');
    }

    /**
     * Synchronize permissions attached to this role per DPPL aturHakAkses().
     *
     * @param  array<int, int>  $permissionIds
     */
    public function aturHakAkses(array $permissionIds): void
    {
        $this->hakAkses()->sync($permissionIds);
    }
}
