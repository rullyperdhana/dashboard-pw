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
        'app_access',
        'skpd_access',
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
        'app_access' => 'array',
        'skpd_access' => 'array',
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

    /**
     * Mendapatkan daftar ID SKPD yang dapat diakses user.
     * Jika superadmin, mengembalikan null (semua).
     */
    public function getAccessibleSkpds()
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        $access = $this->skpd_access ?: [];
        
        // Backward compatibility: include institution if provided
        if ($this->institution && !in_array($this->institution, $access)) {
            $access[] = $this->institution;
        }

        return $access;
    }

    /**
     * Mendapatkan daftar KODE SKPD (format 1.01.01) yang dapat diakses user.
     * Digunakan untuk filter tabel master_pegawai, gaji_pns, dll.
     */
    public function getAccessibleSkpdCodes()
    {
        $ids = $this->getAccessibleSkpds();
        if ($ids === null) {
            return null;
        }

        $codes = \App\Models\Skpd::whereIn('id_skpd', $ids)->pluck('kode_skpd')->toArray();
        $simgajiCodes = \App\Models\Skpd::whereIn('id_skpd', $ids)->whereNotNull('kode_simgaji')->pluck('kode_simgaji')->toArray();
        
        $mappingCodes = \Illuminate\Support\Facades\DB::table('skpd_mapping')
            ->whereIn('skpd_id', $ids)
            ->pluck('source_code')
            ->toArray();

        return array_unique(array_filter(array_merge($codes, $simgajiCodes, $mappingCodes)));
    }
}
