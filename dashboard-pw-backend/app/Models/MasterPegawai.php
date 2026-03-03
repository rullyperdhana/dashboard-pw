<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterPegawai extends Model
{
    use HasFactory;

    protected $table = 'master_pegawai';
    protected $guarded = [];

    public function keluarga()
    {
        return $this->hasMany(MasterKeluarga::class, 'nip', 'nip');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('nama', 'like', "%{$search}%")
            ->orWhere('nip', 'like', "%{$search}%");
    }
}
