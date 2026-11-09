<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payment;
use App\Models\Skpd;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

use App\Traits\CacheClearer;

class DashboardService
{
    use CacheClearer;
    public function getIndexData($user)
    {
        $cacheKey = 'dashboard_index_user_' . $user->id;

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($user) {
            // Total pegawai
            $employeeQuery = Employee::query();
            if ($user->isAdminSkpd()) {
                $employeeQuery->where('idskpd', $user->institution);
            }
            $totalEmployees = $employeeQuery->count();
            // Since we don't have a reliable 'active' flag, all in the table are active PPPK
            $activeEmployees = $totalEmployees;

            // Gender Distribution
            $genderDistribution = (clone $employeeQuery)
                ->select('jk', DB::raw('COUNT(*) as total'))
                ->groupBy('jk')
                ->get();

            // Total pembayaran bulan ini (Latest found in tb_payment)
            $latestPayment = Payment::orderBy('year', 'desc')->orderBy('month', 'desc')->first();
            $monthlyPayment = 0;
            
            // Funding Source Distribution + Realization
            $fundingDistribution = [];
            if ($latestPayment) {
                $fundingQuery = DB::table('pegawai_pw')
                    ->leftJoin('tb_payment_detail', 'pegawai_pw.id', '=', 'tb_payment_detail.employee_id')
                    ->leftJoin('tb_payment', function($join) use ($latestPayment) {
                        $join->on('tb_payment_detail.payment_id', '=', 'tb_payment.id')
                             ->where('tb_payment.year', $latestPayment->year)
                             ->where('tb_payment.month', $latestPayment->month);
                    })
                    ->select(
                        'pegawai_pw.sumber_dana',
                        DB::raw('COUNT(DISTINCT pegawai_pw.id) as total'),
                        DB::raw('SUM(tb_payment_detail.total_amoun) as realization')
                    )
                    ->groupBy('pegawai_pw.sumber_dana');

                if ($user->isAdminSkpd()) {
                    $fundingQuery->where('pegawai_pw.idskpd', $user->institution);
                }

                $fundingDistribution = $fundingQuery->get();

                // Calculate Monthly Payment for display
                $monthlyQuery = DB::table('tb_payment_detail')
                    ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
                    ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
                    ->where('tb_payment.year', $latestPayment->year)
                    ->where('tb_payment.month', $latestPayment->month);

                if ($user->isAdminSkpd()) {
                    $monthlyQuery->where('pegawai_pw.idskpd', $user->institution);
                }

                $monthlyPayment = $monthlyQuery->sum('tb_payment_detail.total_amoun');
            } else {
                $fundingDistribution = (clone $employeeQuery)
                    ->select('sumber_dana', DB::raw('COUNT(*) as total'), DB::raw('0 as realization'))
                    ->groupBy('sumber_dana')
                    ->get();
            }

            // Total SKPD (Only those with PPPK-PW employees)
            $totalSkpd = Skpd::whereIn('id_skpd', function($q) {
                $q->select('idskpd')->from('pegawai_pw');
            })->count();

            // Pegawai per SKPD (All)
            $employeesPerSkpdQuery = DB::table('pegawai_pw')
                ->join('skpd', 'pegawai_pw.idskpd', '=', 'skpd.id_skpd')
                ->select('skpd.nama_skpd', DB::raw('COUNT(*) as total'))
                ->groupBy('skpd.id_skpd', 'skpd.nama_skpd')
                ->orderByDesc('total');

            if ($user->isAdminSkpd()) {
                $employeesPerSkpdQuery->where('pegawai_pw.idskpd', $user->institution);
            }

            $employeesPerSkpd = $employeesPerSkpdQuery->get();

            // Payment trend (6 bulan terakhir)
            $paymentTrendQuery = DB::table('tb_payment_detail')
                ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
                ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
                ->select('tb_payment.month', 'tb_payment.year', DB::raw('SUM(tb_payment_detail.total_amoun) as total'))
                ->groupBy('tb_payment.year', 'tb_payment.month')
                ->orderBy('tb_payment.year', 'desc')
                ->orderBy('tb_payment.month', 'desc')
                ->limit(6);

            if ($user->isAdminSkpd()) {
                $paymentTrendQuery->where('pegawai_pw.idskpd', $user->institution);
            }

            $paymentTrend = $paymentTrendQuery->get()
                ->reverse()
                ->values()
                ->map(function ($item) {
                    $months = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
                    return [
                        'month' => ($months[$item->month] ?? $item->month) . ' ' . $item->year,
                        'total' => (float) $item->total
                    ];
                });

            // Budget Composition (Current Month)
            $composition = [];
            if ($latestPayment) {
                $compQuery = DB::table('tb_payment_detail')
                    ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
                    ->where('tb_payment.year', $latestPayment->year)
                    ->where('tb_payment.month', $latestPayment->month);
                
                if ($user->isAdminSkpd()) {
                    $compQuery->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
                        ->where('pegawai_pw.idskpd', $user->institution);
                }

                $compData = $compQuery->select('tb_payment_detail.gaji_pokok', 'tb_payment_detail.total_amoun')->get();
                $totalGajiPokok = $compData->sum('gaji_pokok');
                $totalBersih = $compData->sum('total_amoun');
                
                $composition = [
                    ['label' => 'Gaji Pokok', 'value' => (float) $totalGajiPokok],
                    ['label' => 'Tunjangan & Lainnya', 'value' => (float) ($totalBersih - $totalGajiPokok)],
                ];
            }

            return [
                'summary' => [
                    'total_employees' => $totalEmployees,
                    'active_employees' => $activeEmployees,
                    'monthly_payment' => (float) $monthlyPayment,
                    'total_skpd' => $totalSkpd,
                ],
                'distribution' => [
                    'gender' => $genderDistribution,
                    'status' => (clone $employeeQuery)
                        ->select('status', DB::raw('COUNT(*) as total'))
                        ->groupBy('status')
                        ->get(),
                    'funding' => $fundingDistribution,
                    'by_jabatan' => (clone $employeeQuery)
                        ->select('jabatan', DB::raw('COUNT(*) as count'))
                        ->whereNotNull('jabatan')
                        ->groupBy('jabatan')
                        ->orderBy('count', 'desc')
                        ->get(),
                    'composition' => $composition,
                ],
                'charts' => [
                    'employees_per_skpd' => $employeesPerSkpd,
                    'payment_trend' => $paymentTrend,
                ],
            ];
        });
    }

    public function getExecutiveSummaryData($month, $year)
    {
        $cacheKey = "executive_summary_{$year}_{$month}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($month, $year) {
            // 1. Employee Counts
            $totalPns = DB::table('gaji_pns')->where('bulan', $month)->where('tahun', $year)->count(DB::raw('DISTINCT nip'));
            $totalPppk = DB::table('gaji_pppk')->where('bulan', $month)->where('tahun', $year)->count(DB::raw('DISTINCT nip'));
            
            $pwStats = DB::table('pegawai_pw')
                ->select('sumber_dana', DB::raw('COUNT(*) as total'))
                ->groupBy('sumber_dana')
                ->pluck('total', 'sumber_dana');
            
            $totalPwApbd = $pwStats['APBD'] ?? 0;
            $totalPwBlud = $pwStats['BLUD'] ?? 0;
            $totalPw = $totalPwApbd + $totalPwBlud;

            // 2. Monthly Expenditure (pure salary gross)
            $expPns = (float) DB::table('gaji_pns')->where('bulan', $month)->where('tahun', $year)->sum(DB::raw('kotor - IFNULL(tunj_tpp, 0)'));
            $expPppk = (float) DB::table('gaji_pppk')->where('bulan', $month)->where('tahun', $year)->sum(DB::raw('kotor - IFNULL(tunj_tpp, 0)'));
            
            $expPwQuery = DB::table('tb_payment_detail as pd')
                ->join('tb_payment as p', 'pd.payment_id', '=', 'p.id')
                ->join('pegawai_pw as pw', 'pd.employee_id', '=', 'pw.id')
                ->where('p.month', $month)
                ->where('p.year', $year);

            $expPwApbd = (float) (clone $expPwQuery)->where('pw.sumber_dana', 'APBD')->sum('pd.total_amoun');
            $expPwBlud = (float) (clone $expPwQuery)->where('pw.sumber_dana', 'BLUD')->sum('pd.total_amoun');
            $expPw = $expPwApbd + $expPwBlud;

            // 3. TPP & Tax Totals (resolve from tpp_details or fallback to table tunj_tpp)
            $tppDetailsPns = DB::table('tpp_details')
                ->where('year', $year)
                ->where('month', $month)
                ->where('employee_type', 'pns')
                ->sum('yang_dibayarkan_transfer');
            $tppPns = $tppDetailsPns > 0 
                ? (float) $tppDetailsPns 
                : (float) DB::table('gaji_pns')->where('bulan', $month)->where('tahun', $year)->sum('tunj_tpp');

            $tppDetailsPppk = DB::table('tpp_details')
                ->where('year', $year)
                ->where('month', $month)
                ->where('employee_type', 'pppk')
                ->sum('yang_dibayarkan_transfer');
            $tppPppk = $tppDetailsPppk > 0 
                ? (float) $tppDetailsPppk 
                : (float) DB::table('gaji_pppk')->where('bulan', $month)->where('tahun', $year)->sum('tunj_tpp');

            $tppStandalone = (float) DB::table('standalone_tpp')->where('month', $month)->where('year', $year)->sum('nilai');

            $taxPns = (float) DB::table('gaji_pns')->where('bulan', $month)->where('tahun', $year)->sum('pot_pph');
            $taxPppk = (float) DB::table('gaji_pppk')->where('bulan', $month)->where('tahun', $year)->sum('pot_pph');

            // 4. Yearly Realization (Jan-Dec for the current year)
            $yearlyRealization = [];
            $monthsArr = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];

            for ($m = 1; $m <= 12; $m++) {
                $mGajiPns = (float) DB::table('gaji_pns')->where('bulan', $m)->where('tahun', $year)->whereNotIn('jenis_gaji', ['THR', 'Gaji 13'])->sum(DB::raw('kotor - IFNULL(tunj_tpp, 0)'));
                $mThrPns = (float) DB::table('gaji_pns')->where('bulan', $m)->where('tahun', $year)->where('jenis_gaji', 'THR')->sum(DB::raw('kotor - IFNULL(tunj_tpp, 0)'));
                $mGaji13Pns = (float) DB::table('gaji_pns')->where('bulan', $m)->where('tahun', $year)->where('jenis_gaji', 'Gaji 13')->sum(DB::raw('kotor - IFNULL(tunj_tpp, 0)'));

                $mTppDetailsPns = DB::table('tpp_details')->where('year', $year)->where('month', $m)->where('employee_type', 'pns')->sum('yang_dibayarkan_transfer');
                $mTppPns = $mTppDetailsPns > 0 
                    ? (float) $mTppDetailsPns 
                    : (float) DB::table('gaji_pns')->where('bulan', $m)->where('tahun', $year)->sum('tunj_tpp');

                $mGajiPppk = (float) DB::table('gaji_pppk')->where('bulan', $m)->where('tahun', $year)->whereNotIn('jenis_gaji', ['THR', 'Gaji 13'])->sum(DB::raw('kotor - IFNULL(tunj_tpp, 0)'));
                $mThrPppk = (float) DB::table('gaji_pppk')->where('bulan', $m)->where('tahun', $year)->where('jenis_gaji', 'THR')->sum(DB::raw('kotor - IFNULL(tunj_tpp, 0)'));
                $mGaji13Pppk = (float) DB::table('gaji_pppk')->where('bulan', $m)->where('tahun', $year)->where('jenis_gaji', 'Gaji 13')->sum(DB::raw('kotor - IFNULL(tunj_tpp, 0)'));

                $mTppDetailsPppk = DB::table('tpp_details')->where('year', $year)->where('month', $m)->where('employee_type', 'pppk')->sum('yang_dibayarkan_transfer');
                $mTppPppk = $mTppDetailsPppk > 0 
                    ? (float) $mTppDetailsPppk 
                    : (float) DB::table('gaji_pppk')->where('bulan', $m)->where('tahun', $year)->sum('tunj_tpp');

                $mPwQuery = DB::table('tb_payment_detail as pd')
                    ->join('tb_payment as p', 'pd.payment_id', '=', 'p.id')
                    ->join('pegawai_pw as pw', 'pd.employee_id', '=', 'pw.id')
                    ->where('p.month', $m)
                    ->where('p.year', $year);

                $mPwApbd = (float) (clone $mPwQuery)->where('pw.sumber_dana', 'APBD')->sum('pd.total_amoun');
                $mPwBlud = (float) (clone $mPwQuery)->where('pw.sumber_dana', 'BLUD')->sum('pd.total_amoun');
                $mPw = $mPwApbd + $mPwBlud;

                $mEmpPns = DB::table('gaji_pns')->where('bulan', $m)->where('tahun', $year)->count(DB::raw('DISTINCT nip'));
                $mEmpPppk = DB::table('gaji_pppk')->where('bulan', $m)->where('tahun', $year)->count(DB::raw('DISTINCT nip'));
                
                // For PW, we only have records if payment exists for that month
                $mEmpPw = DB::table('tb_payment_detail as pd')
                    ->join('tb_payment as p', 'pd.payment_id', '=', 'p.id')
                    ->where('p.month', $m)
                    ->where('p.year', $year)
                    ->count(DB::raw('DISTINCT pd.employee_id'));

                // standalone TPP for this specific month
                $mStandaloneTpp = (float) DB::table('standalone_tpp')->where('month', $m)->where('year', $year)->sum('nilai');

                $mPnsTotal = $mGajiPns + $mThrPns + $mGaji13Pns + $mTppPns + $mStandaloneTpp;
                $mPppkTotal = $mGajiPppk + $mThrPppk + $mGaji13Pppk + $mTppPppk;

                $totalNominal = (float) ($mPnsTotal + $mPppkTotal + $mPw);
                $totalEmployees = $mEmpPns + $mEmpPppk + $mEmpPw;

                $yearlyRealization[] = [
                    'month_name' => $monthsArr[$m],
                    'month_num' => $m,
                    'nominal' => $totalNominal,
                    'employees' => $totalEmployees,
                    'status' => $totalNominal > 0 ? 'paid' : ($m < date('n') ? 'delayed' : 'upcoming'),
                    'breakdown' => [
                        'pns' => [
                            'amount' => (float) $mPnsTotal,
                            'gaji' => (float) $mGajiPns, 
                            'thr' => (float) $mThrPns,
                            'gaji13' => (float) $mGaji13Pns,
                            'tpp' => (float) ($mTppPns + $mStandaloneTpp), 
                            'employees' => $mEmpPns
                        ],
                        'pppk' => [
                            'amount' => (float) $mPppkTotal,
                            'gaji' => (float) $mGajiPppk, 
                            'thr' => (float) $mThrPppk,
                            'gaji13' => (float) $mGaji13Pppk,
                            'tpp' => (float) $mTppPppk, 
                            'employees' => $mEmpPppk
                        ],
                        'pw_apbd' => [
                            'amount' => (float) $mPwApbd, 
                            'gaji' => (float) $mPwApbd, 
                            'thr' => 0,
                            'gaji13' => 0,
                            'tpp' => 0, 
                            'employees' => (clone $mPwQuery)->where('pw.sumber_dana', 'APBD')->count(DB::raw('DISTINCT pd.employee_id'))
                        ],
                        'pw_blud' => [
                            'amount' => (float) $mPwBlud, 
                            'gaji' => (float) $mPwBlud, 
                            'thr' => 0,
                            'gaji13' => 0,
                            'tpp' => 0, 
                            'employees' => (clone $mPwQuery)->where('pw.sumber_dana', 'BLUD')->count(DB::raw('DISTINCT pd.employee_id'))
                        ],
                        'pw' => [
                            'amount' => (float) $mPw, 
                            'gaji' => (float) $mPw, 
                            'thr' => 0,
                            'gaji13' => 0,
                            'tpp' => 0, 
                            'employees' => $mEmpPw
                        ],
                    ]
                ];
            }

            $yearTotal = 0;
            foreach ($yearlyRealization as $yr) {
                $yearTotal += $yr['nominal'];
            }

            return [
                'summary' => [
                    'total_expenditure' => $yearTotal,
                    'total_employees' => $totalPns + $totalPppk + $totalPw,
                    'tpp_total' => (float) ($tppPns + $tppPppk + $tppStandalone),
                    'tax_total' => (float) ($taxPns + $taxPppk),
                    'active_skpd' => DB::table('skpd')->where('is_skpd', 1)->count(),
                    'avg_per_employee' => ($totalPns + $totalPppk + $totalPw) > 0 
                        ? (float) ($yearTotal / ($totalPns + $totalPppk + $totalPw))
                        : 0,
                ],
                'categories' => [
                    ['label' => 'PNS', 'employees' => $totalPns, 'amount' => (float) ($expPns + $tppPns + $tppStandalone), 'gaji' => (float) $expPns, 'tpp' => (float) ($tppPns + $tppStandalone)],
                    ['label' => 'PPPK', 'employees' => $totalPppk, 'amount' => (float) ($expPppk + $tppPppk), 'gaji' => (float) $expPppk, 'tpp' => (float) $tppPppk],
                    ['label' => 'PPPK-PW (APBD)', 'employees' => $totalPwApbd, 'amount' => (float) $expPwApbd, 'gaji' => (float) $expPwApbd, 'tpp' => 0],
                    ['label' => 'PPPK-PW (BLUD)', 'employees' => $totalPwBlud, 'amount' => (float) $expPwBlud, 'gaji' => (float) $expPwBlud, 'tpp' => 0],
                    ['label' => 'PPPK-PW', 'employees' => $totalPw, 'amount' => (float) $expPw, 'gaji' => (float) $expPw, 'tpp' => 0],
                ],
                'yearly_realization' => $yearlyRealization,
                'current_month' => (int)$month,
                'current_year' => (int)$year,
            ];
        });
    }
}
