<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BpjsRekonController extends Controller
{
    /**
     * BPJS 4% Reconciliation Report for PPPK Paruh Waktu.
     *
     * Formula:
     * - Jika gaji_pokok >= UMP → BPJS 4% = gaji_pokok × 4%
     * - Jika gaji_pokok <  UMP → BPJS 4% = UMP × 4% (fixed)
     */
    public function index(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
        ]);

        $month = $request->month;
        $year = $request->year;
        $sumberDana = $request->input('sumber_dana');
        $ump = (float) Setting::getValue('ump_kalsel', 3725000);
        $bpjsUmp = round($ump * 0.04, 0); // BPJS 4% dari UMP

        // Get payment detail with employee info
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
            'pegawai_pw.sumber_dana',
            'tb_payment_detail.gaji_pokok',
            'tb_payment_detail.total_amoun',
            DB::raw("CASE 
                    WHEN tb_payment_detail.gaji_pokok < {$ump} THEN {$bpjsUmp}
                    ELSE ROUND(tb_payment_detail.gaji_pokok * 0.04, 0) 
                END as bpjs_4_persen"),
            DB::raw("CASE 
                    WHEN tb_payment_detail.gaji_pokok < {$ump} THEN 'UMP'
                    ELSE 'GAJI' 
                END as basis_hitung")
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
            DB::raw("SUM(CASE 
                    WHEN tb_payment_detail.gaji_pokok < {$ump} THEN {$bpjsUmp}
                    ELSE ROUND(tb_payment_detail.gaji_pokok * 0.04, 0) 
                END) as total_bpjs_4_persen"),
            DB::raw('SUM(tb_payment_detail.total_amoun) as total_gaji_bersih'),
            DB::raw("SUM(CASE WHEN tb_payment_detail.gaji_pokok < {$ump} THEN 1 ELSE 0 END) as pegawai_bawah_ump")
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
            'pegawai_bawah_ump' => $data->where('basis_hitung', 'UMP')->count(),
            'pegawai_atas_ump' => $data->where('basis_hitung', 'GAJI')->count(),
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
                'ump' => $ump,
                'bpjs_ump' => $bpjsUmp,
            ],
        ]);
    }

    /**
     * Get current UMP setting.
     */
    public function getUmp()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'ump' => (float) Setting::getValue('ump_kalsel', 3725000),
            ],
        ]);
    }

    /**
     * Update UMP setting.
     */
    public function updateUmp(Request $request)
    {
        $request->validate([
            'ump' => 'required|numeric|min:1000000',
        ]);

        Setting::setValue('ump_kalsel', $request->ump, 'UMP Provinsi Kalimantan Selatan untuk dasar perhitungan BPJS 4%');

        return response()->json([
            'success' => true,
            'message' => 'UMP berhasil diperbarui menjadi Rp ' . number_format($request->ump, 0, ',', '.'),
            'data' => [
                'ump' => (float) $request->ump,
                'bpjs_4_persen' => round($request->ump * 0.04, 0),
            ],
        ]);
    }
}
