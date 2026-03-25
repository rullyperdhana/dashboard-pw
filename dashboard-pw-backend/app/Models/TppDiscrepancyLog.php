<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TppDiscrepancyLog extends Model
{
    protected $fillable = [
        'month',
        'year',
        'employee_type',
        'nip',
        'nama',
        'skpd',
        'nilai',
        'reason',
    ];
}
