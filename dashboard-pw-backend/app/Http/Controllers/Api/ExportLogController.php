<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExportLog;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

class ExportLogController extends Controller
{
    /**
     * Display a listing of the export logs.
     * Only accessible by superadmin.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $query = ExportLog::with('user.skpd')->latest();

        // Optional filtering by date or report name
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        if ($request->filled('report_name')) {
            $query->where('report_name', 'like', '%' . $request->report_name . '%');
        }

        // Pagination
        $perPage = $request->per_page ?? 15;
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ]
        ]);
    }

    /**
     * Delete old logs (Superadin only).
     */
    public function cleanup(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        $request->validate([
            'password' => 'required|string',
            'days' => 'required|integer',
        ]);

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password konfirmasi salah.'
            ], 422);
        }

        $days = $request->input('days', 30);
        $date = now()->subDays($days);
        
        $count = ExportLog::where('created_at', '<', $date)->count();
        ExportLog::where('created_at', '<', $date)->delete();

        return response()->json([
            'success' => true,
            'message' => "Berhasil menghapus {$count} log yang lebih tua dari {$days} hari."
        ]);
    }
}
