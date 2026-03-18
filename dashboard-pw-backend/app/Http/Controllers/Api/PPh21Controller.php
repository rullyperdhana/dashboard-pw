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

        $year = $request->year;
        $month = $request->month;
        $type = $request->type;
        $skpd = $request->skpd;

        $model = ($type === 'pns') ? GajiPns::class : GajiPppk::class;
        $query = $model::where('tahun', $year)
            ->where('bulan', $month)
            ->where('jenis_gaji', 'Induk');

        if ($skpd) {
            $query->where('kdskpd', $skpd);
        }

        $records = $query->get();
        $processed = 0;

        foreach ($records as $rec) {
            // 1. Get PTKP Status from MasterPegawai
            $pegawai = MasterPegawai::where('nip', $rec->nip)->first();
            if (!$pegawai) continue;

            $status = $this->service->getPTKPStatus($pegawai->kdstawin, $pegawai->janak);
            $cat = $this->service->getTERCategory($status);

            // 2. Determine Bruto (Simgaji + TPP)
            $bruto = (float)$rec->kotor + (float)$rec->tunj_tpp;
            
            $tax = 0;
            $calcDetails = [
                'gross_simgaji' => (float)$rec->kotor,
                'tpp' => (float)$rec->tunj_tpp,
                'status_ptkp' => $status,
                'ter_category' => $cat,
                'gaji_pokok' => (float)$rec->gaji_pokok,
                'tunj_istri' => (float)$rec->tunj_istri,
                'tunj_anak' => (float)$rec->tunj_anak,
                'tunj_struk_fung' => (float)$rec->tunj_struktural + (float)$rec->tunj_fungsional,
                'tunj_beras' => (float)$rec->tunj_beras,
                'tunj_lain' => (float)$rec->tunj_umum + (float)$rec->tunj_khusus + (float)$rec->tunj_langka + (float)$rec->tunj_pph,
                'pot_iwp' => (float)$rec->pot_iwp
            ];

            if ($month < 12) {
                // JAN - NOV: Standard TER
                $tax = $this->service->calculateMonthlyTER($bruto, $cat);
                $calcDetails['method'] = 'TER';
            } else {
                // DEC: Annual Re-calculation (Pasal 17)
                $prevMonths = DB::table('pph21_calculations')
                    ->where('nip', $rec->nip)
                    ->where('tahun', $year)
                    ->where('bulan', '<', 12)
                    ->get();
                
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

            // 4. Save to Calculations table
            DB::table('pph21_calculations')->updateOrInsert(
                ['nip' => $rec->nip, 'bulan' => $month, 'tahun' => $year, 'jenis_gaji' => 'Induk'],
                [
                    'nama' => $rec->nama,
                    'status_ptkp' => $status,
                    'ter_category' => $cat,
                    'gross_base' => $bruto,
                    'tax_amount' => $tax,
                    'calc_details' => json_encode($calcDetails),
                    'updated_at' => now(),
                    'created_at' => now()
                ]
            );
            $processed++;
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
            ->join('master_pegawai as m', 'c.nip', '=', 'm.nip')
            ->leftJoin('skpd as s', function($join) {
                $join->on(DB::raw('m.kdskpd COLLATE utf8mb4_unicode_ci'), '=', DB::raw('s.kode_simgaji COLLATE utf8mb4_unicode_ci'));
            })
            ->where('c.tahun', $year)
            ->where('c.jenis_gaji', 'Induk');
            
        if ($month) {
            $query->where('c.bulan', $month);
        }

        if ($skpd) {
            // Priority 1: Match by SimGaji Code or SKPD Code (Precise for table buttons)
            $s = DB::table('skpd')
                ->where('kode_simgaji', $skpd)
                ->orWhere('kode_skpd', $skpd)
                ->first();
                
            if ($s) {
                // Use Name to handle duplicates (multiple IDs for same SKPD name)
                $query->where('s.nama_skpd', $s->nama_skpd);
            } else {
                // Priority 2: Match by ID (Top filter fallback)
                $s2 = DB::table('skpd')->where('id_skpd', $skpd)->first();
                if ($s2) {
                    $query->where('s.nama_skpd', $s2->nama_skpd);
                } else {
                    // Priority 3: Raw kdskpd fallback
                    $query->where('m.kdskpd', $skpd);
                }
            }
        }

        $summary = $query->selectRaw('
            c.bulan, 
            m.kdskpd,
            MAX(s.nama_skpd) as nama_skpd,
            COUNT(*) as total_records, 
            SUM(c.gross_base) as total_gross, 
            SUM(c.tax_amount) as total_tax
        ')
        ->groupBy('c.bulan', 'm.kdskpd')
        ->orderBy('c.bulan')
        ->orderBy('m.kdskpd')
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
            ->join('master_pegawai as m', 'c.nip', '=', 'm.nip')
            ->leftJoin('skpd as s', function($join) {
                $join->on(DB::raw('m.kdskpd COLLATE utf8mb4_unicode_ci'), '=', DB::raw('s.kode_simgaji COLLATE utf8mb4_unicode_ci'));
            })
            ->where('c.tahun', $year)
            ->where('c.bulan', $month)
            ->where('c.jenis_gaji', 'Induk');

        if ($skpd) {
            $s = DB::table('skpd')
                ->where('kode_simgaji', $skpd)
                ->orWhere('kode_skpd', $skpd)
                ->first();

            if ($s) {
                $query->where('s.nama_skpd', $s->nama_skpd);
            } else {
                $s2 = DB::table('skpd')->where('id_skpd', $skpd)->first();
                if ($s2) {
                    $query->where('s.nama_skpd', $s2->nama_skpd);
                } else {
                    $query->where('m.kdskpd', $skpd);
                }
            }
        }

        $calcRecords = $query->select('c.*', 'm.nama', 'm.npwp', 'm.noktp', 'm.kdpangkat', 'm.janak', 'm.kdstawin')->get();

        if ($calcRecords->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No calculation records found for export.'], 404);
        }

        // Load Template
        $templatePath = '/Users/rullyperdhana/dashboard-pw/BPA2 Excel to XML.xlsx';
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

                // Identitas (Explicitly use NIK / noktp as NPWP based on user request)
                $sheet->setCellValueExplicit('F' . $row, (string)$rec->noktp, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
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
}
