<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\CacheClearer;

class CacheController extends Controller
{
    use CacheClearer;

    public function clear(Request $request)
    {
        $this->clearDashboardCache();

        return response()->json([
            'success' => true,
            'message' => 'Cache dashboard berhasil dibersihkan.'
        ]);
    }
}
