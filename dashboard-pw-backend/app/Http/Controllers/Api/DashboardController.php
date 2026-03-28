<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Dashboard statistics
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $data = $this->dashboardService->getIndexData($user);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Combined summary for Executive Mobile
     */
    public function executiveSummary(Request $request)
    {
        $month = $request->query('month', date('n'));
        $year = $request->query('year', date('Y'));

        $data = $this->dashboardService->getExecutiveSummaryData($month, $year);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
