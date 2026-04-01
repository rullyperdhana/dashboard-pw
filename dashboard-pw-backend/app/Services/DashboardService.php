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
            if ($latestPayment) {
                $monthlyQuery = DB::table('tb_payment_detail')
                    ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
                    ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
                    ->where('tb_payment.year', $latestPayment->year)
                    ->where('tb_payment.month', $latestPayment->month);

                if ($user->isAdminSkpd()) {
                    $monthlyQuery->where('pegawai_pw.idskpd', $user->institution);
                }

                $monthlyPayment = $monthlyQuery->sum('tb_payment_detail.total_amoun');
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
            $totalPw = DB::table('pegawai_pw')->count();

            // 2. Monthly Expenditure
            $expPns = DB::table('gaji_pns')->where('bulan', $month)->where('tahun', $year)->sum('kotor');
            $expPppk = DB::table('gaji_pppk')->where('bulan', $month)->where('tahun', $year)->sum('kotor');
            
            $expPw = DB::table('tb_payment_detail as pd')
                ->join('tb_payment as p', 'pd.payment_id', '=', 'p.id')
                ->where('p.month', $month)
                ->where('p.year', $year)
                ->sum('pd.total_amoun');

            // 3. TPP & Tax Totals
            $tppPns = DB::table('gaji_pns')->where('bulan', $month)->where('tahun', $year)->sum('tunj_tpp');
            $tppPppk = DB::table('gaji_pppk')->where('bulan', $month)->where('tahun', $year)->sum('tunj_tpp');
            $tppStandalone = DB::table('standalone_tpp')->where('month', $month)->where('year', $year)->sum('nilai');

            $taxPns = DB::table('gaji_pns')->where('bulan', $month)->where('tahun', $year)->sum('pot_pph');
            $taxPppk = DB::table('gaji_pppk')->where('bulan', $month)->where('tahun', $year)->sum('pot_pph');

            // 4. Yearly Realization (Jan-Dec for the current year)
            $yearlyRealization = [];
            $monthsArr = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];

            for ($m = 1; $m <= 12; $m++) {
                $mGajiPns = DB::table('gaji_pns')->where('bulan', $m)->where('tahun', $year)->whereNotIn('jenis_gaji', ['THR', 'Gaji 13'])->sum('kotor');
                $mThrPns = DB::table('gaji_pns')->where('bulan', $m)->where('tahun', $year)->where('jenis_gaji', 'THR')->sum('kotor');
                $mGaji13Pns = DB::table('gaji_pns')->where('bulan', $m)->where('tahun', $year)->where('jenis_gaji', 'Gaji 13')->sum('kotor');
                $mTppPns = DB::table('gaji_pns')->where('bulan', $m)->where('tahun', $year)->sum('tunj_tpp');

                $mGajiPppk = DB::table('gaji_pppk')->where('bulan', $m)->where('tahun', $year)->whereNotIn('jenis_gaji', ['THR', 'Gaji 13'])->sum('kotor');
                $mThrPppk = DB::table('gaji_pppk')->where('bulan', $m)->where('tahun', $year)->where('jenis_gaji', 'THR')->sum('kotor');
                $mGaji13Pppk = DB::table('gaji_pppk')->where('bulan', $m)->where('tahun', $year)->where('jenis_gaji', 'Gaji 13')->sum('kotor');
                $mTppPppk = DB::table('gaji_pppk')->where('bulan', $m)->where('tahun', $year)->sum('tunj_tpp');

                $mPw = DB::table('tb_payment_detail as pd')
                    ->join('tb_payment as p', 'pd.payment_id', '=', 'p.id')
                    ->where('p.month', $m)
                    ->where('p.year', $year)
                    ->sum('pd.total_amoun');

                $mEmpPns = DB::table('gaji_pns')->where('bulan', $m)->where('tahun', $year)->count(DB::raw('DISTINCT nip'));
                $mEmpPppk = DB::table('gaji_pppk')->where('bulan', $m)->where('tahun', $year)->count(DB::raw('DISTINCT nip'));
                
                // For PW, we only have records if payment exists for that month
                $mEmpPw = DB::table('tb_payment_detail as pd')
                    ->join('tb_payment as p', 'pd.payment_id', '=', 'p.id')
                    ->where('p.month', $m)
                    ->where('p.year', $year)
                    ->count(DB::raw('DISTINCT pd.employee_id'));

                $mPnsTotal = $mGajiPns + $mThrPns + $mGaji13Pns;
                $mPppkTotal = $mGajiPppk + $mThrPppk + $mGaji13Pppk;

                // standalone TPP for this specific month
                $mStandaloneTpp = DB::table('standalone_tpp')->where('month', $m)->where('year', $year)->sum('nilai');

                $totalNominal = (float)($mPnsTotal + $mPppkTotal + $mPw + $mStandaloneTpp);
                $totalEmployees = $mEmpPns + $mEmpPppk + $mEmpPw;

                $yearlyRealization[] = [
                    'month_name' => $monthsArr[$m],
                    'month_num' => $m,
                    'nominal' => $totalNominal,
                    'employees' => $totalEmployees,
                    'status' => $totalNominal > 0 ? 'paid' : ($m < date('n') ? 'delayed' : 'upcoming'),
                    'breakdown' => [
                        'pns' => [
                            'amount' => (float)($mPnsTotal + ($m === $month ? $tppStandalone : 0)), // Add standalone only if appropriate
                            'gaji' => (float)$mGajiPns, 
                            'thr' => (float)$mThrPns,
                            'gaji13' => (float)$mGaji13Pns,
                            'tpp' => (float)$mTppPns, 
                            'employees' => $mEmpPns
                        ],
                        'pppk' => [
                            'amount' => (float)($mPppkTotal),
                            'gaji' => (float)$mGajiPppk, 
                            'thr' => (float)$mThrPppk,
                            'gaji13' => (float)$mGaji13Pppk,
                            'tpp' => (float)$mTppPppk, 
                            'employees' => $mEmpPppk
                        ],
                        'pw' => [
                            'amount' => (float)$mPw, 
                            'gaji' => (float)$mPw, 
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
                    'tpp_total' => (float)($tppPns + $tppPppk + $tppStandalone),
                    'tax_total' => (float)($taxPns + $taxPppk),
                    'active_skpd' => DB::table('skpd')->where('is_skpd', 1)->count(),
                    'avg_per_employee' => ($totalPns + $totalPppk + $totalPw) > 0 
                        ? (float)($yearTotal / ($totalPns + $totalPppk + $totalPw))
                        : 0,
                ],
                'categories' => [
                    ['label' => 'PNS', 'employees' => $totalPns, 'amount' => (float)($expPns + $tppStandalone), 'gaji' => (float)$expPns, 'tpp' => (float)($tppPns + $tppStandalone)],
                    ['label' => 'PPPK', 'employees' => $totalPppk, 'amount' => (float)($expPppk), 'gaji' => (float)$expPppk, 'tpp' => (float)$tppPppk],
                    ['label' => 'PPPK-PW', 'employees' => $totalPw, 'amount' => (float)$expPw, 'gaji' => (float)$expPw, 'tpp' => 0],
                ],
                'yearly_realization' => $yearlyRealization,
                'current_month' => (int)$month,
                'current_year' => (int)$year,
            ];
        });
    }
}
