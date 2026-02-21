<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TpgData extends Model
{
    protected $table = 'tpg_data';

    protected $fillable = [
        'nip',
        'nama',
        'no_rekening',
        'satdik',
        'salur_brut',
        'pph',
        'pot_jkn',
        'salur_nett',
        'triwulan',
        'tahun',
        'jenis',
    ];

    protected $casts = [
        'triwulan' => 'integer',
        'tahun' => 'integer',
        'salur_brut' => 'decimal:2',
        'pph' => 'decimal:2',
        'pot_jkn' => 'decimal:2',
        'salur_nett' => 'decimal:2',
    ];
}
