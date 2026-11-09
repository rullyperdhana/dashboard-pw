<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterPegawaiRekon extends Model
{
    use HasFactory;

    protected $table = 'master_pegawai_rekon';
    
    // We will use NIP as primary key, though Laravel defaults to id.
    // It's safer to have an auto-increment id and nip as a unique index or string primary key.
    // We will use nip as primary key.
    protected $primaryKey = 'nip';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nip',
        'nama',
        'status', // PNS / PPPK
        'jenis',  // TEKNIS / KESEHATAN / GURU
    ];
}
