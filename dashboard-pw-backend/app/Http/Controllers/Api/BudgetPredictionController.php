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
        $avgMonthlyTotal = array_sum($monthlyTotals) / count($monthlyTotals);

        $currentDate = Carbon::now();
        $targetDate = Carbon::now()->addYear();
        
        $retiringEmployees = Employee::active()
            ->whereNotNull('tgl_lahir')
            ->get()
            ->filter(function($emp) use ($currentDate, $targetDate) {
                $bup = 58; // Default for PW
                try {
                    $retirementDate = Carbon::parse($emp->tgl_lahir)->addYears($bup);
                    return $retirementDate->between($currentDate, $targetDate);
                } catch (\Exception $e) {
                    return false;
                }
            });
        
        $totalRetirementReduction = 0;
        foreach ($retiringEmployees as $emp) {
            // Find last actual salary in payment system
            $lastSalary = DB::table('tb_payment_detail')
                ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
                ->where('tb_payment_detail.employee_id', $emp->id)
                ->orderBy('tb_payment.year', 'desc')
                ->orderBy('tb_payment.month', 'desc')
                ->value('tb_payment_detail.total_amoun') ?: 0;
            
            // Fallback to gapok if not found in payment system
            if (!$lastSalary) {
                $lastSalary = (float) $emp->gapok + (float) ($emp->tunjangan ?: 0);
            }
            
            $bup = 58;
            $retirementDate = Carbon::parse($emp->tgl_lahir)->addYears($bup);
            $monthsRemaining = $currentDate->diffInMonths($retirementDate);
            $totalRetirementReduction += (12 - $monthsRemaining) * $lastSalary;
        }

        // 3. KGB Simulation for PW (Every 2 years based on tmt_golru)
        $kgbEmployees = Employee::active()
            ->whereNotNull('tmt_golru')
            ->get()
            ->filter(function($emp) use ($currentDate, $targetDate) {
                try {
                    $tmt = Carbon::parse($emp->tmt_golru);
                    // KGB is every 2 years from TMT. We check if a 2-year cycle anniversary falls in the next year.
                    $yearsServed = $tmt->diffInYears($currentDate);
                    $nextCycleYear = (($yearsServed + 1) % 2 === 0) ? ($yearsServed + 1) : ($yearsServed + 2);
                    $nextKgbDate = $tmt->copy()->addYears($nextCycleYear);
                    return $nextKgbDate->between($currentDate, $targetDate);
                } catch (\Exception $e) {
                    return false;
                }
            });

        $totalKgbIncrease = 0;
        foreach ($kgbEmployees as $emp) {
            // Estimate 2.5% increase for KGB if data not available
            $increase = (float) $emp->gapok * 0.025;
            $tmt = Carbon::parse($emp->tmt_golru);
            $yearsServed = $tmt->diffInYears($currentDate);
            $nextCycleYear = (($yearsServed + 1) % 2 === 0) ? ($yearsServed + 1) : ($yearsServed + 2);
            $nextKgbDate = $tmt->copy()->addYears($nextCycleYear);
            $monthsWithRaise = $nextKgbDate->diffInMonths($targetDate);
            $totalKgbIncrease += $monthsWithRaise * $increase;
        }

        return $this->formatResponse($growthFactor, $avgMonthlyTotal, $retiringEmployees, $totalRetirementReduction, $kgbEmployees->count(), $totalKgbIncrease, 0, 0);
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
        $last3Periods = DB::table($table)
            ->select('bulan', 'tahun')
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
                ->sum('bersih');
        }
        $avgMonthlyTotal = array_sum($monthlyTotals) / count($monthlyTotals);

        $currentDate = Carbon::now();
        $targetDate = Carbon::now()->addYear();

        $retiringEmployees = DB::table('master_pegawai')
            ->leftJoin('satkers', function($join) {
                $join->on('master_pegawai.kdskpd', '=', 'satkers.kdskpd')
                    ->on('master_pegawai.kdsatker', '=', 'satkers.kdsatker');
            })
            ->whereIn('master_pegawai.kdstapeg', [1, 2, 3, 4, 5, 11, 12]) // Aktif
            ->whereNotNull('master_pegawai.tgllhr')
            ->where(function($q) use ($table) {
                if ($table === 'gaji_pns') $q->where('master_pegawai.kd_jns_peg', '<', 3);
                else $q->where('master_pegawai.kd_jns_peg', '>=', 3);
            })
            ->select('master_pegawai.*', 'satkers.nmsatker as skpd_name')
            ->get()
            ->filter(function($emp) use ($currentDate, $targetDate, $table) {
                // Gunakan bup dari database, fallback ke 58 jika kosong
                $bup = (int) ($emp->bup ?: 58);
                
                try {
                    $retirementDate = Carbon::parse($emp->tgllhr)->addYears($bup);
                    return $retirementDate->between($currentDate, $targetDate);
                } catch (\Exception $e) {
                    return false;
                }
            });

        $totalRetirementReduction = 0;
        foreach ($retiringEmployees as $emp) {
            $lastSalary = DB::table($table)
                ->where('nip', $emp->nip)
                ->orderBy('tahun', 'desc')
                ->orderBy('bulan', 'desc')
                ->value('bersih') ?: 0;
            
            $bup = (int) ($emp->bup ?: 58);
            $retirementDate = Carbon::parse($emp->tgllhr)->addYears($bup);
            $monthsRemaining = $currentDate->diffInMonths($retirementDate);
            $totalRetirementReduction += (12 - $monthsRemaining) * $lastSalary;
        }

        return $this->formatResponse($growthFactor, $avgMonthlyTotal, $retiringEmployees, $totalRetirementReduction);
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
                    return [
                        'nama' => $e->nama,
                        'nip' => $e->nip,
                        'tgl_lahir' => $e->tgl_lahir ?? $e->tgllhr,
                        'bup' => (int) ($e->bup ?: 58),
                        'skpd' => property_exists($e, 'skpd_name') ? $e->skpd_name : (property_exists($e, 'skpd') ? $e->skpd : null)
                    ];
                })
            ]
        ]);
    }
}
