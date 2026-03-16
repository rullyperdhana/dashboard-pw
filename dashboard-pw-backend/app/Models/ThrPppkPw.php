<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThrPppkPw extends Model
{
    protected $table = 'tb_thr_pppk_pw';

    protected $fillable = [
        'employee_id',
        'year',
        'month',
        'nip',
        'nama',
        'jabatan',
        'skpd_name',
        'kode_sub_giat',
        'nama_sub_giat',
        'gapok_basis',
        'n_months',
        'thr_amount',
        'notes',
        'status',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'gapok_basis' => 'double',
        'n_months' => 'integer',
        'thr_amount' => 'double',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(PegawaiPw::class, 'employee_id');
    }
}
