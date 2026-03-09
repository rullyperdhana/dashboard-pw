<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxStatus extends Model
{
    /** @use HasFactory<\Database\Factories\TaxStatusFactory> */
    use HasFactory;

    protected $table = 'tax_statuses';

    protected $fillable = [
        'nip',
        'nama',
        'employee_type',
        'tax_status',
        'year',
        'is_manual'
    ];

    protected $casts = [
        'year' => 'integer',
        'is_manual' => 'boolean'
    ];
}
