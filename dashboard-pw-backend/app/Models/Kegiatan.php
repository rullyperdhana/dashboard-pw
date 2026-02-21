<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kegiatan extends Model
{
    /**
     * Nama tabel di database
     */
    protected $table = 'kegiatan';

    /**
     * Primary key
     */
    protected $primaryKey = 'id_giat';

    /**
     * Timestamps disabled
     */
    public $timestamps = false;

    /**
     * Kolom yang dapat diisi mass assignment
     */
    protected $fillable = [
        'kode_giat',
        'nama_giat',
    ];

    /**
     * Relasi ke SubKegiatan
     */
    public function subKegiatan()
    {
        return $this->hasMany(SubKegiatan::class, 'id_giat', 'id_giat');
    }

    /**
     * Relasi ke RKA Settings
     */
    public function rkaSettings()
    {
        return $this->hasMany(RkaSetting::class, 'kode_giat', 'kode_giat');
    }
}
