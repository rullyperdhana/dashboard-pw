<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\HandlesExtraPayroll;
use App\Exports\ThrExport;
use App\Models\Setting;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ExtraPayrollPppkPw;

class ThrController extends Controller
{
    use HandlesExtraPayroll;

    protected function getPayrollType(): string
    {
        return 'thr';
    }

    protected function getPayrollLabel(): string
    {
        return 'THR';
    }

    protected function getBasisMonthSettingKey(): string
    {
        return 'thr_pppk_pw_basis_month';
    }

    /**
     * Export to Excel (Specific to THR format/naming)
     */
    public function exportExcel(Request $request)
    {
        $year = $request->year ?? 2026;
        $month = $request->month ?? 4;
        
        $groupedData = $this->getFormattedGroupedData($request);
        $dataArray = json_decode(json_encode($groupedData), true);

        return Excel::download(new ThrExport($dataArray, $year, $month, 'THR'), "THR_PPPK_PW_{$year}_{$month}.xlsx");
    }

    /**
     * Export to PDF (Specific to THR format/naming)
     */
    public function exportPdf(Request $request)
    {
        $year = $request->year ?? 2026;
        $month = $request->month ?: 4;

        $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $method = Setting::where('key', 'thr_pppk_pw_method')->value('value') ?? 'proporsional';
        $reportSettings = (object) [
            'nama_kepala' => Setting::where('key', 'nama_kepala')->value('value'),
            'nip_kepala'  => Setting::where('key', 'nip_kepala')->value('value'),
            'jabatan_kepala' => Setting::where('key', 'jabatan_kepala')->value('value'),
        ];

        $groupedData = $this->getFormattedGroupedData($request);
        $dataArray = json_decode(json_encode($groupedData), true);

        $pdf = Pdf::loadView('reports.thr_pppk_pw', [
            'data'             => $dataArray,
            'year'             => $year,
            'month'            => $month,
            'thrMonthName'     => $monthNames[(int)$month] ?? '',
            'calculationBasis' => 'Data Tersimpan (Database) - Metode: ' . ($method === 'tetap' ? 'Nilai Tetap' : 'Proporsional n/12'),
            'reportSettings'   => $reportSettings,
            'printDate'        => now()->locale('id')->isoFormat('D MMMM YYYY'),
            'thrMethod'        => $method,
            'title'            => 'Tunjangan Hari Raya (THR)'
        ])->setPaper('a4', 'landscape');

        return $pdf->download("THR_PPPK_PW_{$year}_{$month}.pdf");
    }

    public function verifyThr(Request $request)
    {
        return view('verify_thr', [
            'total' => $request->total,
            'period' => $request->period,
            'date' => $request->date,
            'sub_giat' => $request->sub_giat
        ]);
    }
}
