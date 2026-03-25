<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandaloneTpp extends Model
{
    protected $table = 'standalone_tpp';

    protected $fillable = [
        'month',
        'year',
        'employee_type',
        'nip',
        'nama',
        'nilai',
        'jenis_gaji',
        'skpd_id'
    ];

    public function skpd()
    {
        return $this->belongsTo(Skpd::class, 'skpd_id', 'id_skpd');
    }
}
