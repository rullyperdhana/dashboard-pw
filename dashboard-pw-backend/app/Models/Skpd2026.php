<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skpd2026 extends Model
{
    protected $table = 'skpd_2026';

    protected $fillable = [
        'kode_skpd',
        'nama_skpd',
        'is_skpd',
        'kode_simgaji',
        'kode_sipd',
    ];

    protected $casts = [
        'is_skpd' => 'boolean',
    ];
}
