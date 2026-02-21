<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GajiPns extends Model
{
    protected $fillable = [
        'nip',
        'nama',
        'golongan',
        'kdpangkat',
        'jabatan',
        'skpd',
        'satker',
        'kdskpd',
        'kdjenkel',
        'pendidikan',
        'norek',
        'npwp',
        'noktp',
        'gaji_pokok',
        'tunj_istri',
        'tunj_anak',
        'tunj_fungsional',
        'tunj_struktural',
        'tunj_umum',
        'tunj_beras',
        'tunj_pph',
        'tunj_tpp',
        'tunj_eselon',
        'tunj_guru',
        'tunj_langka',
        'tunj_tkd',
        'tunj_terpencil',
        'tunj_khusus',
        'tunj_askes',
        'tunj_kk',
        'tunj_km',
        'pembulatan',
        'kotor',
        'pot_iwp',
        'pot_iwp1',
        'pot_iwp8',
        'pot_askes',
        'pot_pph',
        'pot_bulog',
        'pot_taperum',
        'pot_sewa',
        'pot_hutang',
        'pot_korpri',
        'pot_irdhata',
        'pot_koperasi',
        'pot_jkk',
        'pot_jkm',
        'total_potongan',
        'bersih',
        'bulan',
        'tahun',
        'jenis_gaji'
    ];

    protected $casts = [
        'bulan' => 'integer',
        'tahun' => 'integer',
        'gaji_pokok' => 'decimal:2',
        'bersih' => 'decimal:2',
        'kotor' => 'decimal:2',
    ];
}
