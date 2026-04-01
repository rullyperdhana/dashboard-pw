<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\TppImport;
use App\Models\PayrollPosting;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use App\Services\ExcelValidationService;
use App\Exports\TppTemplateExport; 

use App\Traits\CacheClearer;

class TppController extends Controller
{
    use CacheClearer;
    public function validateUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $expectedHeaders = [
            'NIP', 'NAMA', 'NILAI'
        ];

        $result = ExcelValidationService::validateHeaders($request->file('file'), $expectedHeaders);

        return response()->json($result);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'type' => 'required|in:pns,pppk',
        ]);

        try {
            ini_set('memory_limit', '512M');
            $file = $request->file('file');
            $month = $request->input('month');
            $year = $request->input('year');
            $type = $request->input('type');

            // Check if posted
            if (PayrollPosting::isLocked((int) $year, (int) $month, 'TPP')) {
                return response()->json([
                    'success' => false,
                    'message' => "Data TPP periode {$month}/{$year} sudah di-POSTING (Dikunci) dan tidak dapat diubah."
                ], 403);
            }

            Excel::import(new TppImport($month, $year, $type), $file);

            $this->clearDashboardCache();

            return response()->json([
                'success' => true,
                'message' => 'Data TPP berhasil diimport dan gaji telah dihitung ulang.'
            ]);

        } catch (\Exception $e) {
            Log::error('TPP Upload Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal import data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDiscrepancies(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'type' => 'required|in:pns,pppk',
        ]);

        $logs = \App\Models\TppDiscrepancyLog::where('month', $request->month)
            ->where('year', $request->year)
            ->where('employee_type', $request->type)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    public function downloadTemplate()
    {
        return Excel::download(new TppTemplateExport, 'template_upload_tpp.xlsx');
    }
}
