<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'file_name',
        'file_path',
        'status',
        'progress',
        'total_rows',
        'processed_rows',
        'error_message',
        'error_detail',
        'params',
        'result_summary',
        'user_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'params' => 'array',
        'result_summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function markProcessing()
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markCompleted($summary = [])
    {
        $this->update([
            'status' => 'completed',
            'progress' => 100,
            'completed_at' => now(),
            'result_summary' => $summary,
        ]);
    }

    public function markFailed($message, $detail = null)
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $message,
            'error_detail' => $detail,
        ]);
    }

    public function updateProgress($processed, $total)
    {
        $progress = $total > 0 ? min(100, round(($processed / $total) * 100)) : 0;
        $this->update([
            'processed_rows' => $processed,
            'total_rows' => $total,
            'progress' => $progress,
        ]);
    }
}
