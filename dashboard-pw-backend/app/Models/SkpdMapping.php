<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkpdMapping extends Model
{
    protected $table = 'skpd_mapping';

    protected $fillable = [
        'source_name',
        'skpd_id',
        'type',
    ];

    public function skpd()
    {
        return $this->belongsTo(Skpd::class, 'skpd_id', 'id_skpd');
    }
}
