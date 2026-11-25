<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $table = 'hak_akses';

    protected $fillable = [
        'nama_hak_akses',
    ];

    public function peran(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'peran_hak_akses',
            'id_hak_akses',
            'id_peran'
        );
    }
}
