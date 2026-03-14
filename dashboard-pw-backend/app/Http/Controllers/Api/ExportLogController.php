<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExportLog;
use Illuminate\Http\Request;

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

        $query = ExportLog::with('user')->latest();

        // Optional filtering by date or report name
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        if ($request->has('report_name')) {
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
}
