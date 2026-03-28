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
