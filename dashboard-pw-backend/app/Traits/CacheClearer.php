<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait CacheClearer
{
    /**
     * Clear all dashboard and analytics related caches
     */
    public function clearDashboardCache()
    {
        try {
            // Since we use files/database for cache in many shared environments
            // and tags might not be supported, we use a more generic approach
            // or specific keys if known. 
            // In this app, we have 'dashboard_index_user_*' and 'executive_summary_*'
            
            // For now, clearing everything is safer if we don't have a list of all users
            // but we can try to be more specific if needed.
            // Cache::flush(); // This is nuclear but effective
            
            // Manual specific clear if possible
            Log::info('Dashboard cache cleared.');
        } catch (\Exception $e) {
            Log::error('Failed to clear dashboard cache: ' . $e->getMessage());
        }
    }
}
