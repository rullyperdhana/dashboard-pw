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
        
        $user = auth()->user();
        
        // Pass relevant skpds based on category or handle inside each method
        $pwSkpdIds = $user->getAccessibleSkpds('pw');
        $pnsSkpdCodes = $user->getAccessibleSkpdCodes('pns');

        $data = [
            'age_distribution' => $this->getAgeDistribution($category, $pwSkpdIds, $pnsSkpdCodes),
            'retirement_schedule' => $this->getRetirementSchedule($category, $pwSkpdIds, $pnsSkpdCodes),
            'budget_utilization' => $this->getBudgetUtilization($pwSkpdIds),
            'growth_trends' => $this->getGrowthTrends()
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    private function getAgeDistribution($category, $skpdIds = null, $skpdCodes = null)
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
            $pwQuery = DB::table('pegawai_pw');
            if ($skpdIds !== null) {
                $pwQuery->whereIn('idskpd', $skpdIds);
            }
            $pwAges = $pwQuery->select(DB::raw('TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) AS age'))
                ->get();
            
            foreach ($ranges as $range) {
                $count = $pwAges->whereBetween('age', [$range['min'], $range['max']])->count();
                $results['pw'][] = ['label' => $range['label'], 'value' => $count];
            }
        }

        if ($category === 'all' || $category === 'pns_pppk') {
            $pnsQuery = DB::table('master_pegawai');
            if ($skpdCodes !== null) {
                $pnsQuery->whereIn('kdskpd', $skpdCodes);
            }
            $pnsAges = $pnsQuery->select(DB::raw('TIMESTAMPDIFF(YEAR, tgllhr, CURDATE()) AS age'))
                ->get();

            foreach ($ranges as $range) {
                $count = $pnsAges->whereBetween('age', [$range['min'], $range['max']])->count();
                $results['pns_pppk'][] = ['label' => $range['label'], 'value' => $count];
            }
        }

        return $results;
    }

    private function getRetirementSchedule($category, $skpdIds = null, $skpdCodes = null)
    {
        $schedule = [];
        $currentYear = Carbon::now()->year;

        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear + $i;
            $count = 0;

            if ($category === 'all' || $category === 'pw') {
                $pwQuery = DB::table('pegawai_pw');
                if ($skpdIds !== null) {
                    $pwQuery->whereIn('idskpd', $skpdIds);
                }
                $count += $pwQuery->whereRaw("YEAR(DATE_ADD(tgl_lahir, INTERVAL 58 YEAR)) = ?", [$year])
                    ->count();
            }

            if ($category === 'all' || $category === 'pns_pppk') {
                $pnsQuery = DB::table('master_pegawai');
                if ($skpdCodes !== null) {
                    $pnsQuery->whereIn('kdskpd', $skpdCodes);
                }
                $count += $pnsQuery->whereRaw("YEAR(DATE_ADD(tgllhr, INTERVAL COALESCE(bup, 58) YEAR)) = ?", [$year])
                    ->count();
            }

            $schedule[] = ['year' => $year, 'count' => $count];
        }

        return $schedule;
    }

    private function getBudgetUtilization($skpdIds = null)
    {
        // Last 6 months actual vs base avg
        $query = DB::table('tb_payment_detail')
            ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id');
            
        if ($skpdIds !== null) {
            $query->join('rka_settings', 'tb_payment.rka_id', '=', 'rka_settings.id')
                  ->join('pptk_settings', 'rka_settings.pptk_id', '=', 'pptk_settings.id')
                  ->whereIn('pptk_settings.skpd_id', $skpdIds);
        }

        $trends = $query->select('tb_payment.month', 'tb_payment.year', DB::raw('SUM(tb_payment_detail.total_amoun) as total'))
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
