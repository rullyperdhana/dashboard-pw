<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skpd extends Model
{
    /**
     * Nama tabel di database
     */
    protected $table = 'skpd';

    /**
     * Primary key
     */
    protected $primaryKey = 'id_skpd';

    /**
     * Timestamps disabled (tidak ada created_at/updated_at di tabel)
     */
    public $timestamps = false;

    /**
     * Kolom yang dapat diisi mass assignment
     */
    protected $fillable = [
        'kode_skpd',
        'nama_skpd',
        'is_skpd',
    ];

    /**
     * Cast tipe data
     */
    protected $casts = [
        'is_skpd' => 'boolean',
    ];

    /**
     * Relasi ke Employee (Pegawai)
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'idskpd', 'id_skpd');
    }

    /**
     * Relasi ke User
     */
    public function users()
    {
        return $this->hasMany(User::class, 'institution', 'id_skpd');
    }

    /**
     * Relasi ke PPTK Settings
     */
    public function pptkSettings()
    {
        return $this->hasMany(PptkSetting::class, 'skpd_id', 'id_skpd');
    }

    /**
     * Scope untuk SKPD utama saja
     */
    public function scopeMainSkpd($query)
    {
        return $query->where('is_skpd', 1);
    }

    /**
     * Scope untuk sub-unit
     */
    public function scopeSubUnits($query)
    {
        return $query->where('is_skpd', 0);
    }
}
