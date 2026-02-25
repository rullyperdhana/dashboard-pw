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

        // Total SKPD
        $totalSkpd = Skpd::count();

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
                ],
                'charts' => [
                    'employees_per_skpd' => $employeesPerSkpd,
                    'payment_trend' => $paymentTrend,
                ],
            ],
        ]);
    }
}
