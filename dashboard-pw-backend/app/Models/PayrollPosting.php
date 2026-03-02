<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollPosting extends Model
{
    protected $fillable = [
        'year',
        'month',
        'type',
        'is_posted',
        'posted_at',
        'posted_by',
    ];

    protected $casts = [
        'is_posted' => 'boolean',
        'posted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * Check if a specific payroll period and type is posted.
     */
    public static function isLocked(int $year, int $month, string $type): bool
    {
        return self::where('year', $year)
            ->where('month', $month)
            ->where('type', $type)
            ->where('is_posted', true)
            ->exists();
    }
}
