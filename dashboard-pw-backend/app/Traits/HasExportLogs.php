<?php

namespace App\Traits;

use App\Models\ExportLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait HasExportLogs
{
    /**
     * Log an export activity.
     */
    protected function logExport(string $reportName, string $action, ?string $description = null)
    {
        try {
            ExportLog::create([
                'user_id' => Auth::id(),
                'report_name' => $reportName,
                'action' => $action,
                'description' => $description,
                'ip_address' => Request::ip(),
            ]);
        } catch (\Exception $e) {
            // Silently fail logging to avoid breaking the export itself
            \Log::error("Export logging failed: " . $e->getMessage());
        }
    }
}
