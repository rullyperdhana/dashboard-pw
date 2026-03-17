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

        $groupedData = $this->getFormattedGroupedData($request);
        $dataArray = json_decode(json_encode($groupedData), true);
        
        $pdf = Pdf::loadView('reports.thr_pppk_pw', [
            'data' => $dataArray,
            'year' => $year,
            'month' => $month,
            'title' => 'Gaji Ketiga Belas (Gaji-13)'
        ])->setPaper('a4', 'landscape');

        return $pdf->download("GAJI_13_PPPK_PW_{$year}_{$month}.pdf");
    }
}
