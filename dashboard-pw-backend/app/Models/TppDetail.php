<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TppDetail extends Model
{
    use HasFactory;

    protected $table = 'tpp_details';

    protected $guarded = ['id'];
}
