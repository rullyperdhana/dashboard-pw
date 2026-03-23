<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Http\Request;

class LoginLogController extends Controller
{
    /**
     * Display a listing of the login logs.
     */
    public function index(Request $request)
    {
        // Only superadmin can access this
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $query = LoginLog::orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where('username', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
        }

        $logs = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }
}
