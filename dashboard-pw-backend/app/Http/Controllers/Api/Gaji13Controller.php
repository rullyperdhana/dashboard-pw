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
        $month = $request->month ?: 6;
        $user = auth()->user();

        $job = \App\Models\UploadJob::create([
            'type' => 'export',
            'file_name' => strtoupper($this->getPayrollType()) . "_PPPK_PW_{$year}_{$month}.pdf",
            'file_path' => '-',
            'status' => 'pending',
            'user_id' => $user->id,
            'params' => $request->all()
        ]);

        \App\Jobs\GenerateExtraPayrollPdfJob::dispatch(
            $job->id,
            $this->getPayrollType(),
            $year,
            $month,
            $request->all(),
            $user->id,
            $user->institution,
            $user->role
        );

        return response()->json([
            'success' => true,
            'message' => 'Tugas sedang diproses di background',
            'job_id' => $job->id
        ]);
    }
}
