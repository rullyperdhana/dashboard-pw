<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\Skpd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard statistics
     */
    public function index(Request $request)
    {
        $user = $request->user();

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

        // Pegawai per SKPD (Top 10)
        $employeesPerSkpdQuery = DB::table('pegawai_pw')
            ->join('skpd', 'pegawai_pw.idskpd', '=', 'skpd.id_skpd')
            ->select('skpd.nama_skpd', DB::raw('COUNT(*) as total'))
            ->groupBy('skpd.id_skpd', 'skpd.nama_skpd')
            ->orderByDesc('total')
            ->limit(10);

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

        return response()->json([
            'success' => true,
            'data' => [
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
                    'composition' => $composition,
                ],
                'charts' => [
                    'employees_per_skpd' => $employeesPerSkpd,
                    'payment_trend' => $paymentTrend,
                ],
            ],
        ]);
    }

    /**
    /**
     * Combined summary for Executive Mobile
     */
    public function executiveSummary(Request $request)
    {
        $month = $request->query('month', date('n'));
        $year = $request->query('year', date('Y'));

        // 1. Employee Counts
        $totalPns = DB::table('gaji_pns')->where('bulan', $month)->where('tahun', $year)->count(DB::raw('DISTINCT nip'));
        $totalPppk = DB::table('gaji_pppk')->where('bulan', $month)->where('tahun', $year)->count(DB::raw('DISTINCT nip'));
        $totalPw = DB::table('pegawai_pw')->count();

        // 2. Monthly Expenditure
        $expPns = DB::table('gaji_pns')->where('bulan', $month)->where('tahun', $year)->sum('bersih');
        $expPppk = DB::table('gaji_pppk')->where('bulan', $month)->where('tahun', $year)->sum('bersih');
        
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
            $mPns = DB::table('gaji_pns')->where('bulan', $m)->where('tahun', $year)->sum('bersih');
            $mTppPns = DB::table('gaji_pns')->where('bulan', $m)->where('tahun', $year)->sum('tunj_tpp');
            $mPppk = DB::table('gaji_pppk')->where('bulan', $m)->where('tahun', $year)->sum('bersih');
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

            $totalNominal = (float)($mPns + $mTppPns + $mPppk + $mTppPppk + $mPw);
            $totalEmployees = $mEmpPns + $mEmpPppk + $mEmpPw;

            $yearlyRealization[] = [
                'month_name' => $monthsArr[$m],
                'month_num' => $m,
                'nominal' => $totalNominal,
                'employees' => $totalEmployees,
                'status' => $totalNominal > 0 ? 'paid' : ($m < date('n') ? 'delayed' : 'upcoming'),
                'breakdown' => [
                    'pns' => [
                        'amount' => (float)($mPns + $mTppPns),
                        'gaji' => (float)$mPns, 
                        'tpp' => (float)$mTppPns, 
                        'employees' => $mEmpPns
                    ],
                    'pppk' => [
                        'amount' => (float)($mPppk + $mTppPppk),
                        'gaji' => (float)$mPppk, 
                        'tpp' => (float)$mTppPppk, 
                        'employees' => $mEmpPppk
                    ],
                    'pw' => [
                        'amount' => (float)$mPw, 
                        'gaji' => (float)$mPw, 
                        'tpp' => 0, 
                        'employees' => $mEmpPw
                    ],
                ]
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_expenditure' => (float)($expPns + $tppPns + $tppStandalone + $expPppk + $tppPppk + $expPw),
                    'total_employees' => $totalPns + $totalPppk + $totalPw,
                    'tpp_total' => (float)($tppPns + $tppPppk + $tppStandalone),
                    'tax_total' => (float)($taxPns + $taxPppk),
                    'active_skpd' => DB::table('skpd')->where('is_skpd', 1)->count(),
                    'avg_per_employee' => ($totalPns + $totalPppk + $totalPw) > 0 
                        ? (float)((($expPns + $tppPns + $tppStandalone + $expPppk + $tppPppk + $expPw)) / ($totalPns + $totalPppk + $totalPw))
                        : 0,
                ],
                'categories' => [
                    ['label' => 'PNS', 'employees' => $totalPns, 'amount' => (float)($expPns + $tppPns + $tppStandalone), 'gaji' => (float)$expPns, 'tpp' => (float)($tppPns + $tppStandalone)],
                    ['label' => 'PPPK', 'employees' => $totalPppk, 'amount' => (float)($expPppk + $tppPppk), 'gaji' => (float)$expPppk, 'tpp' => (float)$tppPppk],
                    ['label' => 'PPPK-PW', 'employees' => $totalPw, 'amount' => (float)$expPw, 'gaji' => (float)$expPw, 'tpp' => 0],
                ],
                'yearly_realization' => $yearlyRealization,
                'current_month' => (int)$month,
                'current_year' => (int)$year,
            ]
        ]);
    }
}
