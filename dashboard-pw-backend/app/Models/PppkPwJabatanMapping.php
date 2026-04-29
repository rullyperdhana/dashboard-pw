<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PppkPwJabatanMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_kelompok',
        'kode_rekening',
        'keyword',
        'order_weight',
    ];
}
