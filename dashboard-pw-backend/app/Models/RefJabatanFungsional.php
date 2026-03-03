<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefJabatanFungsional extends Model
{
    protected $table = 'ref_jabatan_fungsional';

    protected $fillable = [
        'kdfungsi',
        'nama_jabatan',
        'tunjangan',
        'usia_pensiun',
        'kelompok_fungsi',
        'tmt_jabatan',
    ];

    protected $casts = [
        'tmt_jabatan' => 'date',
    ];
}
