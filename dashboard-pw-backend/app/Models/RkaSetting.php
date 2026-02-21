<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RkaSetting extends Model
{
    /**
     * Nama tabel di database
     */
    protected $table = 'rka_settings';

    /**
     * Kolom yang dapat diisi mass assignment
     */
    protected $fillable = [
        'pptk_id',
        'kode_giat',
        'nama_giat',
        'kode_sub_giat',
        'nama_sub_giat',
        'tahun',
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
     * Relasi ke PPTK Setting
     */
    public function pptkSetting()
    {
        return $this->belongsTo(PptkSetting::class, 'pptk_id');
    }

    /**
     * Relasi ke Kegiatan
     */
    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'kode_giat', 'kode_giat');
    }

    /**
     * Relasi ke Payment
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'rka_id');
    }

    /**
     * Scope untuk filter berdasarkan tahun
     */
    public function scopeByYear($query, $year)
    {
        return $query->where('tahun', $year);
    }
}
