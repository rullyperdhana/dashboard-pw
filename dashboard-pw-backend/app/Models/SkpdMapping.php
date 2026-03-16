<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkpdMapping extends Model
{
    protected $table = 'skpd_mapping';

    protected $fillable = [
        'source_name',
        'source_code',
        'skpd_id',
        'skpd_2026_id',
        'type',
    ];

    public function skpd()
    {
        return $this->belongsTo(Skpd::class, 'skpd_id', 'id_skpd');
    }

    public function skpd2026()
    {
        return $this->belongsTo(Skpd2026::class, 'skpd_2026_id', 'id');
    }
}
