<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'username',
        'action',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'description',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Helper to quickly log an action
     */
    public static function log(string $action, ?string $description = null, array $extra = []): self
    {
        $user = Auth::user();

        return self::create(array_merge([
            'user_id' => $user?->id,
            'username' => $user?->username ?? $user?->name ?? 'system',
            'action' => $action,
            'description' => $description,
            'ip_address' => Request::ip(),
            'created_at' => now(),
        ], $extra));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
