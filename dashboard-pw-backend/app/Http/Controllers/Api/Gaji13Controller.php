<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\HandlesExtraPayroll;
use App\Exports\ThrExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class Gaji13Controller extends Controller
{
    use HandlesExtraPayroll;

    protected function getPayrollType(): string
    {
        return 'gaji13';
    }

    protected function getPayrollLabel(): string
    {
        return 'Gaji 13';
    }

    protected function getBasisMonthSettingKey(): string
    {
        return 'gaji13_pppk_pw_basis_month';
    }

    public function exportExcel(Request $request)
    {
        $year = $request->year ?? 2026;
        $month = $request->month ?? 6;
        
        $groupedData = $this->getFormattedGroupedData($request);
        $dataArray = json_decode(json_encode($groupedData), true);

        return Excel::download(new ThrExport($dataArray, $year, $month, 'Gaji 13'), "GAJI_13_PPPK_PW_{$year}_{$month}.xlsx");
    }

    public function exportPdf(Request $request)
    {
        $year = $request->year ?? 2026;
        $month = $request->month ?? 6;

        $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $method = \App\Models\Setting::where('key', 'gaji13_pppk_pw_method')->value('value') ?? 'proporsional';
        $reportSettings = (object) [
            'nama_kepala'    => \App\Models\Setting::where('key', 'nama_kepala')->value('value'),
            'nip_kepala'     => \App\Models\Setting::where('key', 'nip_kepala')->value('value'),
            'jabatan_kepala' => \App\Models\Setting::where('key', 'jabatan_kepala')->value('value'),
        ];

        $groupedData = $this->getFormattedGroupedData($request);
        $dataArray = json_decode(json_encode($groupedData), true);

        $pdf = Pdf::loadView('reports.thr_pppk_pw', [
            'data'             => $dataArray,
            'year'             => $year,
            'month'            => $month,
            'thrMonthName'     => $monthNames[$month] ?? '',
            'calculationBasis' => 'Data Tersimpan (Database) — Metode: ' . ($method === 'tetap' ? 'Nilai Tetap' : 'Proporsional n/12'),
            'reportSettings'   => $reportSettings,
            'printDate'        => now()->locale('id')->isoFormat('D MMMM YYYY'),
            'thrMethod'        => $method,
            'title'            => 'Gaji Ketiga Belas (Gaji-13)',
        ])->setPaper('a4', 'landscape');

        return $pdf->download("GAJI_13_PPPK_PW_{$year}_{$month}.pdf");
    }
}
