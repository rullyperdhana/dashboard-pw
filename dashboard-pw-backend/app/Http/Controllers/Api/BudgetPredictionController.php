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
        $user = auth()->user();
        $isSuperAdmin = $user && $user->role === 'superadmin';

        $growthFactor = $request->query('growth_factor', 5);
        $category = $request->query('category');
        
        // If category not provided, default based on role
        if (!$category) {
            $category = $isSuperAdmin ? 'pw' : 'pns';
        }

        // Restrict PW for non-superadmins
        if ($category === 'pw' && !$isSuperAdmin) {
            return response()->json([
                'success' => false, 
                'message' => 'Anda tidak memiliki akses ke data PPPK-PW.'
            ], 403);
        }

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
        $now = Carbon::now()->startOfDay();
        $currentDate = $now->format('Y-m-d');
        $targetDate = $now->copy()->addYear()->format('Y-m-d');

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

        $user = auth()->user();
        $isSuperAdmin = $user->role === 'superadmin';

        $monthlyTotals = [];
        foreach ($last3Months as $p) {
            $query = DB::table('tb_payment_detail')
                ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
                ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
                ->where('tb_payment.month', $p->month)
                ->where('tb_payment.year', $p->year);
            
            if (!$isSuperAdmin) {
                $query->whereIn('pegawai_pw.idskpd', $user->getAccessibleSkpds('pw'));
            }
            
            $monthlyTotals[] = $query->sum(DB::raw('tb_payment_detail.gaji_pokok + tb_payment_detail.tunjangan'));
            $monthlyGajiPokok[] = $query->sum('tb_payment_detail.gaji_pokok');
            $monthlyTunjangan[] = $query->sum('tb_payment_detail.tunjangan');
        }
        $avgMonthlyTotal = count($monthlyTotals) > 0 ? array_sum($monthlyTotals) / count($monthlyTotals) : 0;
        $avgGajiPokok = count($monthlyGajiPokok) > 0 ? array_sum($monthlyGajiPokok) / count($monthlyGajiPokok) : 0;
        $avgTunjangan = count($monthlyTunjangan) > 0 ? array_sum($monthlyTunjangan) / count($monthlyTunjangan) : 0;

        $breakdown = [
            [
                'kode' => '5.1.01.01.0001',
                'nama' => 'Belanja Gaji Pokok PPPK-PW',
                'amount' => ($avgGajiPokok * 14) * (1 + ($growthFactor / 100))
            ],
            [
                'kode' => '5.1.01.01.0002',
                'nama' => 'Belanja Tunjangan PPPK-PW',
                'amount' => ($avgTunjangan * 14) * (1 + ($growthFactor / 100))
            ]
        ];

        // 2. Optimized Retirement Search using SQL
        $retiredQuery = DB::table('pegawai_pw')
            ->leftJoin('skpd', 'pegawai_pw.idskpd', '=', 'skpd.id_skpd')
            ->where('pegawai_pw.status', 'Aktif')
            ->whereNotNull('pegawai_pw.tgl_lahir')
            ->whereRaw("DATE_ADD(pegawai_pw.tgl_lahir, INTERVAL COALESCE(pegawai_pw.usia_bup, 58) YEAR) BETWEEN ? AND ?", [$currentDate, $targetDate]);
        
        if (!$isSuperAdmin) {
            $retiredQuery->whereIn('pegawai_pw.idskpd', $user->getAccessibleSkpds('pw'));
        }

        $retiringEmployees = $retiredQuery->select('pegawai_pw.*', 'skpd.nama_skpd as skpd_name')
            ->get();
        
        $totalRetirementReduction = 0;
        foreach ($retiringEmployees as $emp) {
            // Use gapok + tunjangan as last salary estimate to avoid N+1 queries
            $lastSalary = (float) ($emp->gapok ?? 0) + (float) ($emp->tunjangan ?? 0);
            
            $bup = (int) ($emp->usia_bup ?? 58);
            $retirementDate = Carbon::parse($emp->tgl_lahir)->addYears($bup);
            $monthsRemaining = $now->diffInMonths($retirementDate);
            if ($monthsRemaining < 12) {
                $totalRetirementReduction += (14 - $monthsRemaining) * $lastSalary;
            }
        }

        // 3. KGB Simulation using SQL
        $kgbQuery = DB::table('pegawai_pw')
            ->where('status', 'Aktif')
            ->whereNotNull('tmt_golru')
            ->whereRaw("MOD(TIMESTAMPDIFF(YEAR, tmt_golru, DATE_ADD(?, INTERVAL 1 YEAR)), 2) = 0", [$currentDate]);

        if (!$isSuperAdmin) {
            $kgbQuery->whereIn('idskpd', $user->getAccessibleSkpds('pw'));
        }

        $kgbEmployeesCount = $kgbQuery->count();

        // Estimate KGB increase: 2.5% for those eligible
        // We'll calculate an estimated total increase based on average gapok
        $avgGapok = DB::table('pegawai_pw')->where('status', 'Aktif')->avg('gapok') ?: 0;
        $totalKgbIncrease = $kgbEmployeesCount * ($avgGapok * 0.025) * 6; // Average 6 months of raise

        return $this->formatResponse($growthFactor, $avgMonthlyTotal, $retiringEmployees, $totalRetirementReduction, $kgbEmployeesCount, $totalKgbIncrease, 0, 0, $breakdown);
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
        $now = Carbon::now()->startOfDay();
        $currentDate = $now->format('Y-m-d');
        $targetDate = $now->copy()->addYear()->format('Y-m-d');

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

        $user = auth()->user();
        $isSuperAdmin = $user->role === 'superadmin';

        $monthlyTotals = [];
        foreach ($last3Periods as $p) {
            $query = DB::table($table)
                ->where('bulan', $p->bulan)
                ->where('tahun', $p->tahun)
                ->where('jenis_gaji', 'Induk');
            
            if (!$isSuperAdmin) {
                $query->whereIn('kdskpd', $user->getAccessibleSkpdCodes('pns'));
            }

            $monthlyKotor[] = $query->sum('kotor');
            $monthlyGajiPokok[] = $query->sum('gaji_pokok');
            $monthlyTunjIstri[] = $query->sum('tunj_istri');
            $monthlyTunjAnak[] = $query->sum('tunj_anak');
            $monthlyTunjFungsional[] = $query->sum('tunj_fungsional');
            $monthlyTunjStruktural[] = $query->sum('tunj_struktural');
            $monthlyTunjUmum[] = $query->sum('tunj_umum');
            $monthlyTunjBeras[] = $query->sum('tunj_beras');
            $monthlyTunjPph[] = $query->sum('tunj_pph');
            $monthlyTunjTpp[] = $query->sum('tunj_tpp');
            $monthlyTunjEselon[] = $query->sum('tunj_eselon');
            $monthlyTunjGuru[] = $query->sum('tunj_guru');
            $monthlyPembulatan[] = $query->sum('pembulatan');
        }
        
        $avgMonthlyTotal = count($monthlyKotor) > 0 ? array_sum($monthlyKotor) / count($monthlyKotor) : 0;
        
        $components = [
            ['kode' => '5.1.01.01.0001', 'nama' => 'Gaji Pokok', 'avgs' => $monthlyGajiPokok],
            ['kode' => '5.1.01.01.0002', 'nama' => 'Tunjangan Istri', 'avgs' => $monthlyTunjIstri],
            ['kode' => '5.1.01.01.0003', 'nama' => 'Tunjangan Anak', 'avgs' => $monthlyTunjAnak],
            ['kode' => '5.1.01.01.0004', 'nama' => 'Tunjangan Fungsional', 'avgs' => $monthlyTunjFungsional],
            ['kode' => '5.1.01.01.0005', 'nama' => 'Tunjangan Struktural', 'avgs' => $monthlyTunjStruktural],
            ['kode' => '5.1.01.01.0006', 'nama' => 'Tunjangan Umum', 'avgs' => $monthlyTunjUmum],
            ['kode' => '5.1.01.01.0007', 'nama' => 'Tunjangan Beras', 'avgs' => $monthlyTunjBeras],
            ['kode' => '5.1.01.01.0008', 'nama' => 'Tunjangan PPh', 'avgs' => $monthlyTunjPph],
            ['kode' => '5.1.01.01.0009', 'nama' => 'Tunjangan TPP', 'avgs' => $monthlyTunjTpp],
            ['kode' => '5.1.01.01.0010', 'nama' => 'Tunjangan Eselon', 'avgs' => $monthlyTunjEselon],
            ['kode' => '5.1.01.01.0011', 'nama' => 'Tunjangan Guru', 'avgs' => $monthlyTunjGuru],
            ['kode' => '5.1.01.01.0012', 'nama' => 'Pembulatan', 'avgs' => $monthlyPembulatan],
        ];

        $breakdown = [];
        $calculatedSum = 0;
        foreach ($components as $c) {
            $avg = count($c['avgs']) > 0 ? array_sum($c['avgs']) / count($c['avgs']) : 0;
            $amount = ($avg * 14) * (1 + ($growthFactor / 100));
            $breakdown[] = [
                'kode' => $c['kode'],
                'nama' => $c['nama'],
                'amount' => $amount
            ];
            $calculatedSum += $amount;
        }

        // Add "Lain-lain" for mapping errors or missing columns to ensure total matches
        $remainder = ($avgMonthlyTotal * 12 * (1 + ($growthFactor / 100))) - $calculatedSum;
        if ($remainder > 0) {
            $breakdown[] = [
                'kode' => '5.1.01.99.9999',
                'nama' => 'Tunjangan Lainnya',
                'amount' => $remainder
            ];
        }

        // 2. Optimized Retirement Search using SQL
        $retiredQuery = DB::table('master_pegawai')
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
            ->whereRaw("DATE_ADD(master_pegawai.tgllhr, INTERVAL COALESCE(master_pegawai.bup, 58) YEAR) BETWEEN ? AND ?", [$currentDate, $targetDate]);

        if (!$isSuperAdmin) {
            $retiredQuery->whereIn('master_pegawai.kdskpd', $user->getAccessibleSkpdCodes('pns'));
        }

        $retiringEmployees = $retiredQuery->select('master_pegawai.*', 'satkers.nmsatker as skpd_name')
            ->get();

        $totalRetirementReduction = 0;
        foreach ($retiringEmployees as $emp) {
            // Estimate salary using kotor (Gross)
            $lastSalary = (float) ($emp->kotor ?? 0);
            
            $bup = (int) ($emp->bup ?? 58);
            $retirementDate = Carbon::parse($emp->tgllhr)->addYears($bup);
            $monthsRemaining = $now->diffInMonths($retirementDate);
            if ($monthsRemaining < 12) {
                $totalRetirementReduction += (14 - $monthsRemaining) * $lastSalary;
            }
        }

        // 3. KGB Simulation using SQL (Using tmtkgbyad)
        $kgbQuery = DB::table('master_pegawai')
            ->whereIn('kdstapeg', [1, 2, 3, 4, 5, 11, 12])
            ->whereNotNull('tmtkgbyad')
            ->where(function($q) use ($table) {
                if ($table === 'gaji_pns') $q->where('kd_jns_peg', '<', 3);
                else $q->where('kd_jns_peg', '>=', 3);
            })
            ->whereBetween('tmtkgbyad', [$currentDate, $targetDate]);

        if (!$isSuperAdmin) {
            $kgbQuery->whereIn('kdskpd', $user->getAccessibleSkpdCodes('pns'));
        }

        $kgbEmployeesCount = $kgbQuery->count();

        $avgGapok = DB::table('master_pegawai')->whereIn('kdstapeg', [1, 2, 3, 4, 5, 11, 12])->avg('gapok') ?: 0;
        $totalKgbIncrease = $kgbEmployeesCount * ($avgGapok * 0.03) * 6;

        // 4. KP (Kenaikan Pangkat) Simulation using SQL
        $kpQuery = DB::table('master_pegawai')
            ->whereIn('kdstapeg', [1, 2, 3, 4, 5, 11, 12])
            ->whereNotNull('blgolt')
            ->where(function($q) use ($table) {
                if ($table === 'gaji_pns') $q->where('kd_jns_peg', '<', 3);
                else $q->where('kd_jns_peg', '>=', 3);
            })
            ->whereRaw("MOD(TIMESTAMPDIFF(YEAR, blgolt, DATE_ADD(?, INTERVAL 1 YEAR)), 4) = 0", [$currentDate]);

        if (!$isSuperAdmin) {
            $kpQuery->whereIn('kdskpd', $user->getAccessibleSkpdCodes('pns'));
        }

        $kpEmployeesCount = $kpQuery->count();

        $totalKpIncrease = $kpEmployeesCount * ($avgGapok * 0.06) * 6;

        return $this->formatResponse(
            $growthFactor, 
            $avgMonthlyTotal, 
            $retiringEmployees, 
            $totalRetirementReduction,
            $kgbEmployeesCount,
            $totalKgbIncrease,
            $kpEmployeesCount,
            $totalKpIncrease,
            $breakdown
        );
    }

    private function formatResponse($growthFactor, $avgMonthlyTotal, $retiringEmployees, $totalRetirementReduction, $kgbCount = 0, $kgbAmount = 0, $kpCount = 0, $kpAmount = 0, $breakdown = [])
    {
        $baseYearly = $avgMonthlyTotal * 14;
        $afterEvents = $baseYearly - $totalRetirementReduction + $kgbAmount + $kpAmount;
        $finalForecast = $afterEvents * (1 + ($growthFactor / 100));

        // Adjust breakdown total to exactly match finalForecast
        $breakdownTotal = array_sum(array_column($breakdown, 'amount'));
        if ($breakdownTotal > 0) {
            $ratio = $finalForecast / $breakdownTotal;
            foreach ($breakdown as &$item) {
                $item['amount'] *= $ratio;
            }
        }

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
                'breakdown' => $breakdown,
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
