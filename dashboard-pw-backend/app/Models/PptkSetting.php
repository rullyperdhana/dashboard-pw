<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PptkSetting extends Model
{
    /**
     * Nama tabel di database
     */
    protected $table = 'pptk_settings';

    /**
     * Kolom yang dapat diisi mass assignment
     */
    protected $fillable = [
        'tahun',
        'nip_pptk',
        'nama_pptk',
        'pangkat_pptk',
        'skpd_id',
    ];

    /**
     * Cast tipe data
     */
    protected $casts = [
        'tahun' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Timestamps - hanya created_at
     */
    const UPDATED_AT = null;

    /**
     * Relasi ke SKPD
     */
    public function skpd()
    {
        return $this->belongsTo(Skpd::class, 'skpd_id', 'id_skpd');
    }

    /**
     * Relasi ke RKA Settings (kegiatan yang dikelola)
     */
    public function rkaSettings()
    {
        return $this->hasMany(RkaSetting::class, 'pptk_id');
    }

    /**
     * Scope untuk filter berdasarkan tahun
     */
    public function scopeByYear($query, $year)
    {
        return $query->where('tahun', $year);
    }
}
