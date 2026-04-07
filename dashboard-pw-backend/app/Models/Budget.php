<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $fillable = [
        'skpd_id',
        'tahun',
        'jenis_anggaran',
        'tipe_anggaran',
        'nominal',
        'keterangan'
    ];

    protected $casts = [
        'nominal' => 'float',
        'tahun' => 'integer'
    ];

    public function skpd()
    {
        return $this->belongsTo(Skpd::class, 'skpd_id', 'id_skpd');
    }
}
