<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Get organization health analytics (Age & Retirement Peaks)
     */
    public function health(Request $request)
    {
        $category = $request->query('category', 'all');
        
        $data = [
            'age_distribution' => $this->getAgeDistribution($category),
            'retirement_schedule' => $this->getRetirementSchedule($category),
            'budget_utilization' => $this->getBudgetUtilization(),
            'growth_trends' => $this->getGrowthTrends()
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    private function getAgeDistribution($category)
    {
        // Define age ranges
        $ranges = [
            ['min' => 20, 'max' => 30, 'label' => '20-30 th'],
            ['min' => 31, 'max' => 40, 'label' => '31-40 th'],
            ['min' => 41, 'max' => 50, 'label' => '41-50 th'],
            ['min' => 51, 'max' => 60, 'label' => '51-60 th'],
        ];

        $results = [];

        if ($category === 'all' || $category === 'pw') {
            $pwAges = DB::table('pegawai_pw')
                ->select(DB::raw('TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) AS age'))
                ->get();
            
            foreach ($ranges as $range) {
                $count = $pwAges->whereBetween('age', [$range['min'], $range['max']])->count();
                $results['pw'][] = ['label' => $range['label'], 'value' => $count];
            }
        }

        if ($category === 'all' || $category === 'pns_pppk') {
            $pnsAges = DB::table('master_pegawai')
                ->select(DB::raw('TIMESTAMPDIFF(YEAR, tgllhr, CURDATE()) AS age'))
                ->get();

            foreach ($ranges as $range) {
                $count = $pnsAges->whereBetween('age', [$range['min'], $range['max']])->count();
                $results['pns_pppk'][] = ['label' => $range['label'], 'value' => $count];
            }
        }

        return $results;
    }

    private function getRetirementSchedule($category)
    {
        $schedule = [];
        $currentYear = Carbon::now()->year;

        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear + $i;
            $count = 0;

            if ($category === 'all' || $category === 'pw') {
                $count += DB::table('pegawai_pw')
                    ->whereRaw("YEAR(DATE_ADD(tgl_lahir, INTERVAL 58 YEAR)) = ?", [$year])
                    ->count();
            }

            if ($category === 'all' || $category === 'pns_pppk') {
                $count += DB::table('master_pegawai')
                    ->whereRaw("YEAR(DATE_ADD(tgllhr, INTERVAL COALESCE(bup, 58) YEAR)) = ?", [$year])
                    ->count();
            }

            $schedule[] = ['year' => $year, 'count' => $count];
        }

        return $schedule;
    }

    private function getBudgetUtilization()
    {
        // Last 6 months actual vs base avg
        $trends = DB::table('tb_payment_detail')
            ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
            ->select('tb_payment.month', 'tb_payment.year', DB::raw('SUM(tb_payment_detail.total_amoun) as total'))
            ->groupBy('tb_payment.year', 'tb_payment.month')
            ->orderBy('tb_payment.year', 'desc')
            ->orderBy('tb_payment.month', 'desc')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        return $trends;
    }

    private function getGrowthTrends()
    {
        // Cumulative spending over year
        return [];
    }
}
