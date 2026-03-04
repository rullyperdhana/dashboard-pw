<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefEselon extends Model
{
    protected $table = 'ref_eselon';
    protected $primaryKey = 'kd_eselon';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kd_eselon',
        'rp_eselon',
        'uraian',
        'bup',
    ];
}
