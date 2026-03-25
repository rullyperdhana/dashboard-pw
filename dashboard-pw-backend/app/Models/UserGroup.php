<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'app_access',
        'skpd_access',
        'description',
    ];

    protected $casts = [
        'app_access' => 'array',
        'skpd_access' => 'array',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
