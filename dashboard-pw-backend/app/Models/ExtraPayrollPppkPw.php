<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraPayrollPppkPw extends Model
{
    use HasFactory;

    protected $table = 'tb_extra_payroll_pppk_pw';

    protected $fillable = [
        'employee_id',
        'year',
        'month',
        'type', // 'thr' or 'gaji13'
        'nip',
        'nama',
        'jabatan',
        'skpd_name',
        'kode_sub_giat',
        'nama_sub_giat',
        'pptk_nama',
        'pptk_nip',
        'pptk_jabatan',
        'gapok_basis',
        'n_months',
        'payroll_amount',
        'notes',
        'status',
    ];

    protected $casts = [
        'gapok_basis' => 'float',
        'payroll_amount' => 'float',
        'n_months' => 'integer',
        'year' => 'integer',
        'month' => 'integer',
    ];
}
