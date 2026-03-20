<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GajiPns;
use App\Models\GajiPppk;
use App\Models\MasterPegawai;
use App\Services\PPh21Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PPh21Controller extends Controller
{
    protected $service;

    public function __construct(PPh21Service $service)
    {
        $this->service = $service;
    }

    /**
     * Trigger PPh 21 calculation for a month/year
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer',
            'skpd' => 'nullable|string',
            'type' => 'required|in:pns,pppk'
        ]);

        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $year = $request->year;
        $month = $request->month;
        $type = $request->type;
        $skpd = $request->skpd;

        $user = auth()->user();
        $records = collect();
        
        // Comprehensive SKPD Mapping: 
        // 1. From skpd_mapping (source_code -> skpd_id)
        // 2. From skpd table directly (kode_simgaji -> id_skpd)
        $skpdIdMap = DB::table('skpd_mapping')->pluck('skpd_id', 'source_code')->toArray();
        $directSimGajiMap = DB::table('skpd')->whereNotNull('kode_simgaji')->pluck('id_skpd', 'kode_simgaji')->toArray();
        $skpdIdMap = $skpdIdMap + $directSimGajiMap; // Mapping takes priority if duplicates

        $table = ($type === 'pns') ? 'gaji_pns' : 'gaji_pppk';
        $query = DB::table($table . ' as g')
            ->join('master_pegawai as m', 'g.nip', '=', 'm.nip')
            ->where('g.tahun', $year)
            ->where('g.bulan', $month)
            ->where('g.jenis_gaji', 'Induk');

        if ($skpd) {
            if (!$user->isSuperAdmin()) {
                $allowedIds = $user->getAccessibleSkpds();
                // Resolve ID to codes if needed, or just check inclusion in IDs
                $allowedIds = $user->getAccessibleSkpds();
                if (!in_array($skpd, $allowedIds)) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized SKPD access.'], 403);
                }
            }
            
            // Resolve the provided skpd_id to its SimGaji codes for filtering the legacy table
            $targetCodes = DB::table('skpd')
                ->where('id_skpd', $skpd)
                ->whereNotNull('kode_simgaji')
                ->pluck('kode_simgaji')
                ->merge(
                    DB::table('skpd_mapping')
                        ->where('skpd_id', $skpd)
                        ->pluck('source_code')
                )
                ->unique()
                ->toArray();
            
            if (empty($targetCodes)) {
                // Fallback to kode_skpd if no mapping/simgaji code
                $kodeSkpd = DB::table('skpd')->where('id_skpd', $skpd)->value('kode_skpd');
                if ($kodeSkpd) $targetCodes = [$kodeSkpd];
            }

            $query->whereIn('g.kdskpd', $targetCodes);
        } else {
            $skpds = $user->getAccessibleSkpdCodes();
            if ($skpds !== null) {
                $query->whereIn('g.kdskpd', $skpds);
            }
        }

        $records = $query->select('g.*', 'm.kdstawin', 'm.janak')->get()->map(function($r) use ($skpdIdMap) {
            $code = trim((string)$r->kdskpd);
            $r->skpd_id = $skpdIdMap[$code] ?? null;
            return $r;
        });
        
        $processed = 0;
        $this->service->preLoadRates();
        
        // Fetch extra payrolls
        // Note: For PW, we currently don't have separate extra payroll records in SimGaji tables
        $extraGaji = collect()->groupBy('nip');
        if (isset($table)) {
            $extraGaji = DB::table($table)
                ->where('tahun', $year)
                ->where('bulan', $month)
                ->where('jenis_gaji', '!=', 'Induk')
                ->get()
                ->groupBy('nip');
        }

        $extraPayroll = collect();
        if ($type === 'pppk') {
            $extraPayroll = DB::table('tb_extra_payroll_pppk_pw')
                ->where('year', $year)
                ->where('month', $month)
                ->get()
                ->groupBy('nip');
        }

        // Optimasi Desember: Ambil semua data perhitungan sebelumnya dalam satu query
        $prevCalculationsGrouped = [];
        if ($month == 12) {
            $prevCalculationsGrouped = DB::table('pph21_calculations')
                ->where('tahun', $year)
                ->where('bulan', '<', 12)
                ->where('jenis_gaji', 'Induk')
                ->get()
                ->groupBy('nip');
        }

        $upsertData = [];
        foreach ($records as $rec) {
            $status = $this->service->getPTKPStatus($rec->kdstawin, $rec->janak);
            $cat = $this->service->getTERCategory($status);

            // 2. Determine Bruto (Simgaji + TPP + Extra Payroll)
            $extraSum = $extraGaji->get($rec->nip, collect())->sum('kotor');
            $extraPayrollSum = $extraPayroll->get($rec->nip, collect())->sum('payroll_amount');
            
            $bruto = (float)$rec->kotor + (float)$rec->tunj_tpp + $extraSum + $extraPayrollSum;
            
            $tax = 0;
            $calcDetails = [
                'gross_simgaji' => (float)$rec->kotor,
                'tpp' => (float)$rec->tunj_tpp,
                'extra_salary' => $extraSum,
                'extra_payroll' => $extraPayrollSum,
                'status_ptkp' => $status,
                'ter_category' => $cat,
                'gaji_pokok' => (float)$rec->gaji_pokok,
                'tunj_istri' => (float)$rec->tunj_istri,
                'tunj_anak' => (float)$rec->tunj_anak,
                'tunj_struk_fung' => (float)$rec->tunj_struktural + (float)$rec->tunj_fungsional,
                'tunj_beras' => (float)$rec->tunj_beras,
                'tunj_lain' => (float)$rec->tunj_umum + (float)$rec->tunj_khusus + (float)$rec->tunj_langka + (float)$rec->tunj_pph,
                'pot_iwp' => (float)$rec->pot_iwp,
                'extra_records' => $extraGaji->get($rec->nip, collect())->toArray(),
                'extra_payroll_records' => $extraPayroll->get($rec->nip, collect())->toArray(),
            ];

            if ($month < 12) {
                // JAN - NOV: Standard TER
                $tax = $this->service->calculateMonthlyTER($bruto, $cat);
                $calcDetails['method'] = 'TER';
            } else {
                // DEC: Annual Re-calculation (Pasal 17)
                $prevMonths = $prevCalculationsGrouped->get($rec->nip, collect());
                
                $totalGross = $prevMonths->sum('gross_base') + $bruto;
                $totalPaid = $prevMonths->sum('tax_amount');
                
                // Deductions (Biaya Jabatan: 5% max 6M/year)
                $bj = min(6000000, $totalGross * 0.05);
                
                // Iuran Pensiun / THT
                $totalIuran = 0;
                foreach($prevMonths as $pm) {
                    $pmDetails = json_decode($pm->calc_details, true);
                    $totalIuran += ($pmDetails['pot_iwp'] ?? 0);
                }
                $totalIuran += (float)$rec->pot_iwp;
                
                $ptkp = $this->service->getPTKPAmount($status);
                $pkp = max(0, $totalGross - $bj - $totalIuran - $ptkp);
                $pkpRounded = floor($pkp / 1000) * 1000;
                
                $annualTax = $this->service->calculateAnnualPasal17($pkpRounded);
                $tax = max(0, $annualTax - $totalPaid);
                
                $calcDetails['method'] = 'Pasal 17';
                $calcDetails['annual_gross'] = $totalGross;
                $calcDetails['annual_pkp'] = $pkpRounded;
                $calcDetails['annual_tax_total'] = $annualTax;
                $calcDetails['tax_paid_jan_nov'] = $totalPaid;
                $calcDetails['annual_iuran'] = $totalIuran;
            }

            // 4. Collect for bulk upsert
            $upsertData[] = [
                'nip' => $rec->nip,
                'skpd_id' => $rec->skpd_id,
                'bulan' => $month,
                'tahun' => $year,
                'jenis_gaji' => 'Induk',
                'nama' => $rec->nama,
                'status_ptkp' => $status,
                'ter_category' => $cat,
                'gross_base' => $bruto,
                'tax_amount' => $tax,
                'calc_details' => json_encode($calcDetails),
                'updated_at' => now(),
                'created_at' => now()
            ];
            $processed++;

            // Batch upsert every 500 records to save memory
            if (count($upsertData) >= 500) {
                $this->doUpsert($upsertData);
                $upsertData = [];
            }
        }

        // Final upsert
        if (!empty($upsertData)) {
            $this->doUpsert($upsertData);
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully processed $processed records.",
            'processed' => $processed
        ]);
    }

    /**
     * Get summary report of PPh 21
     */
    public function report(Request $request)
    {
        $request->validate(['year' => 'required|integer', 'month' => 'nullable|integer', 'skpd' => 'nullable']);
        
        $year = $request->year;
        $month = $request->month;
        $skpd = $request->skpd;

        $query = DB::table('pph21_calculations as c')
            ->leftJoin('skpd as s', 'c.skpd_id', '=', 's.id_skpd')
            ->where('c.tahun', $year)
            ->where('c.jenis_gaji', 'Induk');

        $user = auth()->user();
        $skpdIds = $user->getAccessibleSkpds();
        if ($skpdIds !== null) {
            $query->whereIn('c.skpd_id', $skpdIds);
        }
            
        if ($month) {
            $query->where('c.bulan', $month);
        }

        if ($skpd) {
            $query->where('c.skpd_id', $skpd);
        }

        $summary = $query->selectRaw('
            c.bulan, 
            c.skpd_id,
            COALESCE(MAX(s.nama_skpd), CONCAT("SKPD ID: ", c.skpd_id)) as nama_skpd,
            COUNT(*) as total_records, 
            SUM(c.gross_base) as total_gross, 
            SUM(c.tax_amount) as total_tax
        ')
        ->groupBy('c.bulan', 'c.skpd_id')
        ->orderBy('c.bulan')
        ->orderBy('c.skpd_id')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Export Bukti Potong A2 to Excel
     */
    public function exportA2(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'nullable|integer',
            'skpd' => 'nullable'
        ]);

        $year = $request->year;
        $month = $request->month ?? 12; // Default to year-end
        $skpd = $request->skpd;

        $query = DB::table('pph21_calculations as c')
            ->leftJoin('skpd as s', 'c.skpd_id', '=', 's.id_skpd')
            ->where('c.tahun', $year)
            ->where('c.bulan', $month)
            ->where('c.jenis_gaji', 'Induk');

        $user = auth()->user();
        $skpdIds = $user->getAccessibleSkpds();
        if ($skpdIds !== null) {
            $query->whereIn('c.skpd_id', $skpdIds);
        }

        if ($skpd) {
            $query->where('c.skpd_id', $skpd);
        }

        $calcRecords = $query->select('c.*')->get();

        if ($calcRecords->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No calculation records found for export.'], 404);
        }

        // Load Template
        $templatePath = base_path('BPA2 Excel to XML.xlsx');
        if (!file_exists($templatePath)) {
            return response()->json(['success' => false, 'message' => 'Template file not found.'], 404);
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Set Tanggal Pemotongan (AA) - typically last day of the month
            $withholdingDate = date('Y-m-t', strtotime("$year-$month-01"));

            // Add Header for easier manual checking
            $sheet->setCellValue('A3', 'Nama Pegawai');

            $row = 4; // Start from row 4 based on analysis
            foreach ($calcRecords as $idx => $rec) {
                $details = is_string($rec->calc_details) ? json_decode($rec->calc_details, true) : (array)$rec->calc_details;

                // Employee Name (Leftmost for manual verification)
                $sheet->setCellValue('A' . $row, $rec->nama);

                // Index/No
                $sheet->setCellValue('B' . $row, $idx + 1);
                
                // Masa Pajak
                $sheet->setCellValue('C' . $row, $month == 12 ? 1 : $month);
                $sheet->setCellValue('D' . $row, $month);
                $sheet->setCellValue('E' . $row, $year);

                // Identitas
                $sheet->setCellValueExplicit('F' . $row, (string)($details['nik'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('G' . $row, (string)$rec->nip, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                $sheet->setCellValue('H' . $row, $rec->status_ptkp);
                $sheet->setCellValue('I' . $row, 'Pegawai Tetap');
                $sheet->setCellValue('J' . $row, $rec->kdpangkat);
                $sheet->setCellValue('K' . $row, '21-100-01');
                $sheet->setCellValue('L' . $row, $month == 12 ? 'Biasa' : 'Biasa'); // Default status
                
                // Income mapping based on identified columns
                $sheet->setCellValue('N' . $row, $details['gaji_pokok'] ?? 0);
                $sheet->setCellValue('O' . $row, $details['tunj_istri'] ?? 0);
                $sheet->setCellValue('P' . $row, $details['tunj_anak'] ?? 0);
                $sheet->setCellValue('Q' . $row, $details['tpp'] ?? 0);
                $sheet->setCellValue('R' . $row, $details['tunj_struk_fung'] ?? 0);
                $sheet->setCellValue('S' . $row, $details['tunj_beras'] ?? 0);
                $sheet->setCellValue('T' . $row, $details['tunj_lain'] ?? 0);
                $sheet->setCellValue('V' . $row, $details['pot_iwp'] ?? 0);
                
                // Tax
                $sheet->setCellValue('Y' . $row, $rec->tax_amount);
                
                // AA: Tanggal Pemotongan
                $sheet->setCellValue('AA' . $row, $withholdingDate);
                
                $row++;
            }

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $fileName = "Bukti_Potong_PPh21_A2_{$year}_{$month}.xlsx";
            $tempFile = tempnam(sys_get_temp_dir(), 'pph21');
            $writer->save($tempFile);

            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('A2 Export Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to generate Excel: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete PPh 21 calculation records (Superadmin only)
     */
    public function destroy(Request $request)
    {
        if (auth()->user()->role !== 'superadmin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'year' => 'required|integer',
            'month' => 'nullable|integer',
            'skpd' => 'nullable',
            'items' => 'nullable|array'
        ]);

        $year = $request->year;
        $totalDeleted = 0;

        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $item) {
                $m = $item['month'] ?? null;
                $s = $item['skpd'] ?? null;
                
                if ($m) {
                    $q = DB::table('pph21_calculations')
                        ->where('tahun', $year)
                        ->where('bulan', $m);
                    
                    if ($s !== null) {
                        $q->where('skpd_id', $s);
                    }
                    
                    $totalDeleted += $q->delete();
                }
            }
        } else {
            $query = DB::table('pph21_calculations')
                ->where('tahun', $year);

            if ($request->month) {
                $query->where('bulan', $request->month);
            }

            if ($request->skpd) {
                $query->where('skpd_id', $request->skpd);
            }

            $totalDeleted = $query->delete();
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted $totalDeleted records."
        ]);
    }

    private function doUpsert(array $data)
    {
        DB::table('pph21_calculations')->upsert(
            $data, 
            ['nip', 'bulan', 'tahun', 'jenis_gaji'], 
            ['nama', 'skpd_id', 'status_ptkp', 'ter_category', 'gross_base', 'tax_amount', 'calc_details', 'updated_at']
        );
    }
}
