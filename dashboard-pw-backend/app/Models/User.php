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
        'user_group_id',
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
     * Relasi ke User Group
     */
    public function userGroup()
    {
        return $this->belongsTo(UserGroup::class, 'user_group_id');
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
     * @param string|null $type 'pns' or 'pw'
     */
    public function getAccessibleSkpds($type = null)
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        $rawAccess = $this->skpd_access;
        
        // If user specific access is empty, fallback to group access
        if (empty($rawAccess) && $this->user_group_id) {
            $rawAccess = $this->userGroup->skpd_access ?? [];
        }

        $rawAccess = $rawAccess ?: [];
        $access = [];

        // Normalize access based on type
        if (is_array($rawAccess)) {
            foreach ($rawAccess as $item) {
                if (is_array($item) || is_object($item)) {
                    $item = (object) $item;
                    $pns = property_exists($item, 'pns') ? $item->pns : true;
                    $pw = property_exists($item, 'pw') ? $item->pw : true;
                    $id = property_exists($item, 'id') ? $item->id : (property_exists($item, 'id_skpd') ? $item->id_skpd : null);
                    
                    if (!$id) continue;

                    if ($type === 'pns' && $pns) $access[] = $id;
                    elseif ($type === 'pw' && $pw) $access[] = $id;
                    elseif ($type === null) $access[] = $id;
                } else {
                    // Backward compatibility for simple ID list
                    $access[] = $item;
                }
            }
        }
        
        // Backward compatibility: include institution if provided
        if ($this->institution && !in_array($this->institution, $access)) {
            $access[] = $this->institution;
        }

        // Smart Hierarchy Logic: Include children/sub-units of assigned SKPDs
        if (!empty($access)) {
            $parentCodes = \App\Models\Skpd::whereIn('id_skpd', $access)
                ->where('is_skpd', 1)
                ->pluck('kode_skpd')
                ->map(function($code) {
                    $lastDot = strrpos($code, '.');
                    if ($lastDot === false) return null;
                    return substr($code, 0, $lastDot + 1);
                })
                ->filter()
                ->unique();

            if ($parentCodes->isNotEmpty()) {
                $childIds = \App\Models\Skpd::where(function($query) use ($parentCodes) {
                    foreach ($parentCodes as $prefix) {
                        $query->orWhere('kode_skpd', 'like', $prefix . '%');
                    }
                })->pluck('id_skpd')->toArray();
                
                $access = array_unique(array_merge($access, array_map('intval', $childIds)));
            }
        }

        return $access;
    }

    /**
     * Accessor untuk app_access dengan fallback ke User Group
     */
    public function getAppAccessAttribute($value)
    {
        $access = json_decode($value, true);
        
        // If user specific access is empty, fallback to group access
        if (empty($access) && $this->user_group_id) {
            $access = $this->userGroup->app_access ?? [];
        }

        return $access ?: [];
    }

    /**
     * Mendapatkan daftar KODE SKPD (format 1.01.01) yang dapat diakses user.
     * @param string|null $type 'pns' or 'pw'
     */
    public function getAccessibleSkpdCodes($type = null)
    {
        $ids = $this->getAccessibleSkpds($type);
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
