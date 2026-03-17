<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BudgetPredictionController extends Controller
{
    /**
     * Get budget prediction for next year
     */
    public function index(Request $request)
    {
        $growthFactor = $request->query('growth_factor', 5);
        $category = $request->query('category', 'pw');

        if ($category === 'pw') {
            return $this->predictPW($growthFactor, $request);
        } elseif ($category === 'pns') {
            return $this->predictPNS($growthFactor, $request);
        } elseif ($category === 'pppk') {
            return $this->predictPPPK($growthFactor, $request);
        }

        return response()->json(['success' => false, 'message' => 'Kategori tidak valid.']);
    }

    private function predictPW($growthFactor, Request $request)
    {
        $currentDate = Carbon::now()->format('Y-m-d');
        $targetDate = Carbon::now()->addYear()->format('Y-m-d');

        // 1. Get last 3 unique months with data
        $last3Months = DB::table('tb_payment')
            ->select('year', 'month')
            ->distinct()
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(3)
            ->get();
        
        if ($last3Months->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Data pembayaran PW tidak mencukupi.']);
        }

        $monthlyTotals = [];
        foreach ($last3Months as $p) {
            $monthlyTotals[] = DB::table('tb_payment_detail')
                ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
                ->where('tb_payment.month', $p->month)
                ->where('tb_payment.year', $p->year)
                ->sum('tb_payment_detail.total_amoun');
        }
        $avgMonthlyTotal = count($monthlyTotals) > 0 ? array_sum($monthlyTotals) / count($monthlyTotals) : 0;

        // 2. Optimized Retirement Search using SQL
        $retiringEmployees = DB::table('pegawai_pw')
            ->leftJoin('skpd', 'pegawai_pw.idskpd', '=', 'skpd.id_skpd')
            ->where('pegawai_pw.status', 'Aktif')
            ->whereNotNull('pegawai_pw.tgl_lahir')
            ->whereRaw("DATE_ADD(pegawai_pw.tgl_lahir, INTERVAL COALESCE(pegawai_pw.usia_bup, 58) YEAR) BETWEEN ? AND ?", [$currentDate, $targetDate])
            ->select('pegawai_pw.*', 'skpd.nama_skpd as skpd_name')
            ->get();
        
        $totalRetirementReduction = 0;
        foreach ($retiringEmployees as $emp) {
            // Use gapok + tunjangan as last salary estimate to avoid N+1 queries
            $lastSalary = (float) ($emp->gapok ?? 0) + (float) ($emp->tunjangan ?? 0);
            
            $bup = (int) ($emp->usia_bup ?? 58);
            $retirementDate = Carbon::parse($emp->tgl_lahir)->addYears($bup);
            $monthsRemaining = Carbon::now()->diffInMonths($retirementDate);
            $totalRetirementReduction += (12 - $monthsRemaining) * $lastSalary;
        }

        // 3. KGB Simulation using SQL
        $kgbEmployeesCount = DB::table('pegawai_pw')
            ->where('status', 'Aktif')
            ->whereNotNull('tmt_golru')
            ->whereRaw("MOD(TIMESTAMPDIFF(YEAR, tmt_golru, DATE_ADD(?, INTERVAL 1 YEAR)), 2) = 0", [$currentDate])
            ->count();

        // Estimate KGB increase: 2.5% for those eligible
        // We'll calculate an estimated total increase based on average gapok
        $avgGapok = DB::table('pegawai_pw')->where('status', 'Aktif')->avg('gapok') ?: 0;
        $totalKgbIncrease = $kgbEmployeesCount * ($avgGapok * 0.025) * 6; // Average 6 months of raise

        return $this->formatResponse($growthFactor, $avgMonthlyTotal, $retiringEmployees, $totalRetirementReduction, $kgbEmployeesCount, $totalKgbIncrease, 0, 0);
    }

    private function predictPNS($growthFactor, Request $request)
    {
        return $this->predictMasterPegawai('gaji_pns', $growthFactor, $request);
    }

    private function predictPPPK($growthFactor, Request $request)
    {
        return $this->predictMasterPegawai('gaji_pppk', $growthFactor, $request);
    }

    private function predictMasterPegawai($table, $growthFactor, Request $request)
    {
        $currentDate = Carbon::now()->format('Y-m-d');
        $targetDate = Carbon::now()->addYear()->format('Y-m-d');

        $last3Periods = DB::table($table)
            ->select('bulan', 'tahun')
            ->where('jenis_gaji', 'Induk')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->limit(3)
            ->get();

        if ($last3Periods->isEmpty()) {
            return response()->json(['success' => false, 'message' => "Data {$table} tidak mencukupi."]);
        }

        $monthlyTotals = [];
        foreach ($last3Periods as $p) {
            $monthlyTotals[] = DB::table($table)
                ->where('bulan', $p->bulan)
                ->where('tahun', $p->tahun)
                ->where('jenis_gaji', 'Induk')
                ->sum('bersih');
        }
        $avgMonthlyTotal = count($monthlyTotals) > 0 ? array_sum($monthlyTotals) / count($monthlyTotals) : 0;

        // 2. Optimized Retirement Search using SQL
        $retiringEmployees = DB::table('master_pegawai')
            ->leftJoin('satkers', function($join) {
                $join->on('master_pegawai.kdskpd', '=', 'satkers.kdskpd')
                    ->on('master_pegawai.kdsatker', '=', 'satkers.kdsatker');
            })
            ->whereIn('master_pegawai.kdstapeg', [1, 2, 3, 4, 5, 11, 12])
            ->whereNotNull('master_pegawai.tgllhr')
            ->where(function($q) use ($table) {
                if ($table === 'gaji_pns') $q->where('master_pegawai.kd_jns_peg', '<', 3);
                else $q->where('master_pegawai.kd_jns_peg', '>=', 3);
            })
            ->whereRaw("DATE_ADD(master_pegawai.tgllhr, INTERVAL COALESCE(master_pegawai.bup, 58) YEAR) BETWEEN ? AND ?", [$currentDate, $targetDate])
            ->select('master_pegawai.*', 'satkers.nmsatker as skpd_name')
            ->get();

        $totalRetirementReduction = 0;
        foreach ($retiringEmployees as $emp) {
            // Estimate salary using gapok + some allowances if available
            $lastSalary = (float) ($emp->gapok ?? 0);
            if (isset($emp->tjfungsi)) $lastSalary += (float) $emp->tjfungsi;
            if (isset($emp->tjistri)) $lastSalary += (float) $emp->tjistri;
            
            $bup = (int) ($emp->bup ?? 58);
            $retirementDate = Carbon::parse($emp->tgllhr)->addYears($bup);
            $monthsRemaining = Carbon::now()->diffInMonths($retirementDate);
            $totalRetirementReduction += (12 - $monthsRemaining) * $lastSalary;
        }

        // 3. KGB Simulation using SQL (Using tmtkgbyad)
        $kgbEmployeesCount = DB::table('master_pegawai')
            ->whereIn('kdstapeg', [1, 2, 3, 4, 5, 11, 12])
            ->whereNotNull('tmtkgbyad')
            ->where(function($q) use ($table) {
                if ($table === 'gaji_pns') $q->where('kd_jns_peg', '<', 3);
                else $q->where('kd_jns_peg', '>=', 3);
            })
            ->whereBetween('tmtkgbyad', [$currentDate, $targetDate])
            ->count();

        $avgGapok = DB::table('master_pegawai')->whereIn('kdstapeg', [1, 2, 3, 4, 5, 11, 12])->avg('gapok') ?: 0;
        $totalKgbIncrease = $kgbEmployeesCount * ($avgGapok * 0.03) * 6;

        // 4. KP (Kenaikan Pangkat) Simulation using SQL
        $kpEmployeesCount = DB::table('master_pegawai')
            ->whereIn('kdstapeg', [1, 2, 3, 4, 5, 11, 12])
            ->whereNotNull('blgolt')
            ->where(function($q) use ($table) {
                if ($table === 'gaji_pns') $q->where('kd_jns_peg', '<', 3);
                else $q->where('kd_jns_peg', '>=', 3);
            })
            ->whereRaw("MOD(TIMESTAMPDIFF(YEAR, blgolt, DATE_ADD(?, INTERVAL 1 YEAR)), 4) = 0", [$currentDate])
            ->count();

        $totalKpIncrease = $kpEmployeesCount * ($avgGapok * 0.06) * 6;

        return $this->formatResponse(
            $growthFactor, 
            $avgMonthlyTotal, 
            $retiringEmployees, 
            $totalRetirementReduction,
            $kgbEmployeesCount,
            $totalKgbIncrease,
            $kpEmployeesCount,
            $totalKpIncrease
        );
    }

    private function formatResponse($growthFactor, $avgMonthlyTotal, $retiringEmployees, $totalRetirementReduction, $kgbCount = 0, $kgbAmount = 0, $kpCount = 0, $kpAmount = 0)
    {
        $baseYearly = $avgMonthlyTotal * 12;
        $afterEvents = $baseYearly - $totalRetirementReduction + $kgbAmount + $kpAmount;
        $finalForecast = $afterEvents * (1 + ($growthFactor / 100));

        return response()->json([
            'success' => true,
            'data' => [
                'parameters' => [
                    'growth_factor' => (float) $growthFactor,
                    'avg_monthly_base' => (float) $avgMonthlyTotal,
                ],
                'factors' => [
                    'retiring_count' => $retiringEmployees->count(),
                    'retirement_savings' => (float) $totalRetirementReduction,
                    'kgb_count' => $kgbCount,
                    'kgb_investment' => (float) $kgbAmount,
                    'kp_count' => $kpCount,
                    'kp_investment' => (float) $kpAmount,
                ],
                'projection' => [
                    'base_yearly' => (float) $baseYearly,
                    'with_events' => (float) $afterEvents,
                    'final_forecast' => (float) $finalForecast,
                    'monthly_avg_forecast' => (float) ($finalForecast / 12)
                ],
                'retiring_list' => $retiringEmployees->values()->map(function($e) {
                    $bup = (int) ($e->usia_bup ?? $e->bup ?? 58);
                    $dob = $e->tgl_lahir ?? $e->tgllhr ?? null;
                    $retirementDate = null;
                    if ($dob) {
                        try {
                            $retirementDate = Carbon::parse($dob)->addYears($bup)->format('Y-m-d');
                        } catch (\Exception $ex) {}
                    }
                    
                    return [
                        'nama' => $e->nama ?? 'N/A',
                        'nip' => $e->nip ?? 'N/A',
                        'tgl_lahir' => $dob,
                        'retirement_date' => $retirementDate,
                        'bup' => $bup,
                        'skpd' => property_exists($e, 'skpd_name') ? $e->skpd_name : (property_exists($e, 'skpd') ? $e->skpd : null)
                    ];
                })
            ]
        ]);
    }
}
