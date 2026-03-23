<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BkdPegawai extends Model
{
    protected $table = 'bkd_pegawai';

    protected $fillable = [
        'nip', 'nama', 'nik', 'jabatan', 'golongan',
        'tgl_lahir', 'jenis_kelamin', 'upload_batch',
    ];

    protected $casts = [
        'tgl_lahir' => 'date',
    ];
}
