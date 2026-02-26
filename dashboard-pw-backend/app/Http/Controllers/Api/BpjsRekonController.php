<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BpjsRekonController extends Controller
{
    /**
     * BPJS 4% Reconciliation Report for PPPK Paruh Waktu.
     * Calculates BPJS 4% based on gaji_pokok from tb_payment_detail.
     */
    public function index(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
        ]);

        $month = $request->month;
        $year = $request->year;
        $sumberDana = $request->input('sumber_dana'); // optional: APBD or BLUD

        // Get payment detail with employee info for the given period
        $query = DB::table('tb_payment_detail')
            ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
            ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
            ->where('tb_payment.month', $month)
            ->where('tb_payment.year', $year);

        if ($sumberDana) {
            $query->where('pegawai_pw.sumber_dana', $sumberDana);
        }

        $data = $query->select(
            'pegawai_pw.nip',
            'pegawai_pw.nama',
            'pegawai_pw.skpd',
            'pegawai_pw.upt',
            'pegawai_pw.jabatan',
            'tb_payment_detail.gaji_pokok',
            'tb_payment_detail.total_amoun',
            DB::raw('ROUND(tb_payment_detail.gaji_pokok * 0.04, 0) as bpjs_4_persen')
        )
            ->orderBy('pegawai_pw.skpd')
            ->orderBy('pegawai_pw.nama')
            ->get();

        // Summary per SKPD
        $skpdQuery = DB::table('tb_payment_detail')
            ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
            ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
            ->where('tb_payment.month', $month)
            ->where('tb_payment.year', $year);

        if ($sumberDana) {
            $skpdQuery->where('pegawai_pw.sumber_dana', $sumberDana);
        }

        $skpdSummary = $skpdQuery->select(
            'pegawai_pw.skpd',
            DB::raw('COUNT(*) as jumlah_pegawai'),
            DB::raw('SUM(tb_payment_detail.gaji_pokok) as total_gaji_pokok'),
            DB::raw('SUM(ROUND(tb_payment_detail.gaji_pokok * 0.04, 0)) as total_bpjs_4_persen'),
            DB::raw('SUM(tb_payment_detail.total_amoun) as total_gaji_bersih')
        )
            ->groupBy('pegawai_pw.skpd')
            ->orderBy('pegawai_pw.skpd')
            ->get();

        // Grand total
        $grandTotal = [
            'jumlah_pegawai' => $data->count(),
            'total_gaji_pokok' => $data->sum('gaji_pokok'),
            'total_bpjs_4_persen' => $data->sum('bpjs_4_persen'),
            'total_gaji_bersih' => $data->sum('total_amoun'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'detail' => $data,
                'skpd_summary' => $skpdSummary,
                'grand_total' => $grandTotal,
                'period' => [
                    'month' => $month,
                    'year' => $year,
                ],
            ],
        ]);
    }
}
