<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PegawaiPw extends Model
{
    use HasFactory;

    protected $table = 'pegawai_pw';

    protected $fillable = [
        'idskpd',
        'nip',
        'nama',
        'tempat_lahir',
        'tgl_lahir',
        'jk',
        'status',
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
        'potongan'
    ];

    protected $casts = [
        'tgl_lahir' => 'date',
        'tmt_golru' => 'date',
        'tmt_jabatan' => 'date',
        'gapok' => 'decimal:2',
        'tunjangan' => 'decimal:2',
        'pajak' => 'decimal:2',
        'iwp' => 'decimal:2',
        'potongan' => 'decimal:2',
        'mk_thn' => 'integer',
        'mk_bln' => 'integer',
        'idskpd' => 'integer'
    ];

    /**
     * Get the SKPD associated with the employee.
     */
    public function skpdRel()
    {
        return $this->belongsTo(Skpd::class, 'idskpd', 'id_skpd');
    }
}
