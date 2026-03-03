<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefStapeg extends Model
{
    protected $table = 'ref_stapeg';
    protected $primaryKey = 'kdstapeg';
    public $incrementing = false;

    protected $fillable = ['kdstapeg', 'nmstapeg'];
}
