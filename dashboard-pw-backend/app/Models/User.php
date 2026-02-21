<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'institution',
        'nip',
        'username',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Relasi ke SKPD
     */
    public function skpd()
    {
        return $this->belongsTo(Skpd::class, 'institution', 'id_skpd');
    }

    /**
     * Relasi ke Employee (Pegawai)
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'nip', 'nip');
    }

    /**
     * Check apakah user adalah superadmin
     */
    public function isSuperAdmin()
    {
        return $this->role === 'superadmin';
    }

    /**
     * Check apakah user adalah admin SKPD
     */
    public function isAdminSkpd()
    {
        return $this->role === 'operator' && !empty($this->institution);
    }

    /**
     * Check apakah user adalah operator
     */
    public function isOperator()
    {
        return $this->role === 'operator';
    }

    /**
     * Scope untuk user aktif
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope untuk filter berdasarkan SKPD
     */
    public function scopeBySkpd($query, $skpdId)
    {
        return $query->where('institution', $skpdId);
    }
}
