<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sp2dRealization extends Model
{
    protected $fillable = [
        'nomor_sp2d',
        'tanggal_sp2d',
        'tanggal_cair',
        'nama_skpd_sipd',
        'skpd_id',
        'keterangan',
        'jenis_data',
        'brutto',
        'potongan',
        'netto',
        'bulan',
        'tahun',
        'is_manual',
    ];

    protected $casts = [
        'tanggal_sp2d' => 'date',
        'tanggal_cair' => 'date',
        'brutto' => 'float',
        'potongan' => 'float',
        'netto' => 'float',
    ];

    public function skpd()
    {
        return $this->belongsTo(Skpd::class, 'skpd_id', 'id_skpd');
    }
}
