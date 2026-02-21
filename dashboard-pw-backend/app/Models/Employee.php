<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    /**
     * Nama tabel di database
     */
    protected $table = 'pegawai_pw';

    /**
     * Primary key
     */
    protected $primaryKey = 'id';

    /**
     * Kolom yang dapat diisi mass assignment
     */
    protected $fillable = [
        'idskpd',
        'nip',
        'nik',
        'nama',
        'tempat_lahir',
        'tgl_lahir',
        'jk',
        'status',
        'no_hp',
        'agama',
        'golru',
        'tmt_golru',
        'jabatan',
        'eselon',
        'jenis_jabatan',
        'tmt_jabatan',
        'skpd',
        'upt',
        'satker',
        'mk_thn',
        'mk_bln',
        'tk_ijazah',
        'nm_pendidikan',
        'th_lulus',
        'usia',
        'usia_bup',
        'keterangan',
        'gapok',
        'tunjangan',
        'pajak',
        'iwp',
        'potongan',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'tgl_lahir' => 'date',
        'tmt_golru' => 'date',
        'tmt_jabatan' => 'date',
        'gapok' => 'double',
        'tunjangan' => 'double',
        'pajak' => 'double',
        'iwp' => 'double',
        'potongan' => 'double',
        'mk_thn' => 'integer',
        'mk_bln' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke SKPD
     */
    public function skpd()
    {
        return $this->belongsTo(Skpd::class, 'idskpd', 'id_skpd');
    }

    /**
     * Relasi ke User (jika ada akun login)
     */
    public function user()
    {
        return $this->hasOne(User::class, 'nip', 'nip');
    }

    /**
     * Relasi ke PaymentDetail
     */
    public function paymentDetails()
    {
        return $this->hasMany(PaymentDetail::class, 'employee_id', 'id');
    }

    /**
     * Scope untuk filter berdasarkan SKPD
     */
    public function scopeBySkpd($query, $skpdId)
    {
        return $query->where('idskpd', $skpdId);
    }

    /**
     * Scope untuk pegawai aktif
     */
    public function scopeActive($query)
    {
        // Assuming all employees in the table are active or based on gapok
        return $query->whereNotNull('nip');
    }

    /**
     * Accessor untuk nama lengkap
     */
    public function getFullNameAttribute()
    {
        return $this->nama;
    }
}
