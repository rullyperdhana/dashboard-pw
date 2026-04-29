<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentDetail;
use App\Models\Payment;
use App\Models\RkaSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PppkPwMonthlyReportExport;

class PppkPwReportController extends Controller
{
    public function monthlyReport(Request $request)
    {
        $month = $request->month;
        $year = $request->year;
        $idskpd = $request->idskpd;
        $rka_id = $request->rka_id;

        $query = PaymentDetail::select(
            'tb_payment_detail.*',
            'p.month',
            'p.year',
            'p.rka_id',
            'rs.nama_sub_giat',
            'pw.nip',
            'pw.nama',
            'pw.jabatan',
            'pw.skpd',
            'pw.idskpd',
            'pw.sumber_dana'
        )
        ->join('tb_payment as p', 'tb_payment_detail.payment_id', '=', 'p.id')
        ->leftJoin('rka_settings as rs', 'p.rka_id', '=', 'rs.id')
        ->join('pegawai_pw as pw', 'tb_payment_detail.employee_id', '=', 'pw.id');

        if ($month) $query->where('p.month', $month);
        if ($year) $query->where('p.year', $year);
        if ($idskpd) $query->where('pw.skpd', $idskpd);
        if ($rka_id) $query->where('p.rka_id', $rka_id);

        $data = $query->orderBy('pw.nama')->get();

        // Calculate summaries
        $summary = [
            'total_pegawai' => $data->count(),
            'total_gaji_pokok' => $data->sum('gaji_pokok'),
            'total_potongan' => $data->sum(function($item) {
                return $item->pajak + $item->iwp + $item->potongan;
            }),
            'total_bersih' => $data->sum('total_amoun'),
        ];

        // Filter data
        $skpds = DB::table('skpd')
            ->select('nama_skpd as title', 'nama_skpd as value')
            ->groupBy('nama_skpd')
            ->orderBy('nama_skpd')
            ->get();
            
        $subKegiatans = DB::table('rka_settings')
            ->select('nama_sub_giat as title', 'id as value', 'kode_sub_giat')
            ->orderBy('nama_sub_giat')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => $summary,
            'filters' => [
                'skpds' => $skpds,
                'sub_kegiatans' => $subKegiatans
            ]
        ]);
    }

    public function exportExcel(Request $request)
    {
        $month = $request->month;
        $year = $request->year;
        $idskpd = $request->idskpd;
        $rka_id = $request->rka_id;

        $filename = "laporan_pppk_pw_monthly_{$month}_{$year}.xlsx";
        return Excel::download(new PppkPwMonthlyReportExport($month, $year, $idskpd, $rka_id), $filename);
    }
}
