<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryGajiPokok extends Model
{
    use HasFactory;

    protected $table = 'history_gaji_pokok';
    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(MasterPegawai::class, 'nip', 'nip');
    }
}
