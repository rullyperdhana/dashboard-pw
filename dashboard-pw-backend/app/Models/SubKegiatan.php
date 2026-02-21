<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubKegiatan extends Model
{
    /**
     * Nama tabel di database
     */
    protected $table = 'sub_kegiatan';

    /**
     * Primary key
     */
    protected $primaryKey = 'id_sub_giat';

    /**
     * Timestamps disabled
     */
    public $timestamps = false;

    /**
     * Kolom yang dapat diisi mass assignment
     */
    protected $fillable = [
        'id_giat',
        'kode_sub_giat',
        'nama_sub_giat',
    ];

    /**
     * Relasi ke Kegiatan (parent)
     */
    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'id_giat', 'id_giat');
    }
}
