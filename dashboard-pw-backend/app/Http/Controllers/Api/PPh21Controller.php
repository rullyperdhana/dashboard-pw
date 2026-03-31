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

        try {
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
            
            // Safety check for kode_simgaji column existence (in case migration desynced on VPS)
            $directSimGajiMap = [];
            if (\Illuminate\Support\Facades\Schema::hasColumn('skpd', 'kode_simgaji')) {
                $directSimGajiMap = DB::table('skpd')->whereNotNull('kode_simgaji')->pluck('id_skpd', 'kode_simgaji')->toArray();
            }
            
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
                    if (!in_array($skpd, $allowedIds)) {
                        return response()->json(['success' => false, 'message' => 'Unauthorized SKPD access.'], 403);
                    }
                }
                
                // Resolve the provided skpd_id to its SimGaji codes for filtering the legacy table
                $targetCodesQuery = DB::table('skpd')->where('id_skpd', $skpd);
                
                if (\Illuminate\Support\Facades\Schema::hasColumn('skpd', 'kode_simgaji')) {
                    $targetCodesQuery->whereNotNull('kode_simgaji');
                    $targetCodes = $targetCodesQuery->pluck('kode_simgaji')
                        ->merge(
                            DB::table('skpd_mapping')
                                ->where('skpd_id', $skpd)
                                ->pluck('source_code')
                        )
                        ->unique()
                        ->toArray();
                } else {
                    $targetCodes = DB::table('skpd_mapping')
                        ->where('skpd_id', $skpd)
                        ->pluck('source_code')
                        ->unique()
                        ->toArray();
                }
                
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

            $records = $query->select('g.*', 'm.kdstawin', 'm.janak', 'm.kdpangkat', 'm.noktp', 'm.npwp')->get()->map(function($r) use ($skpdIdMap) {
                $code = trim((string)$r->kdskpd);
                $r->skpd_id = $skpdIdMap[$code] ?? null;
                return $r;
            });
            
            $processed = 0;
            $this->service->preLoadRates();
            
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

            // Optimasi Desember
            $prevCalculationsGrouped = [];
            if ($month == 12) {
                $prevCalculationsGrouped = DB::table('pph21_calculations')
                    ->where('tahun', $year)
                    ->where('bulan', '<', 12)
                    ->where('jenis_gaji', 'Induk')
                    ->get()
                    ->groupBy('nip');
            }

            $fixedTaxStatuses = DB::table('tax_statuses')
                ->where('year', $year)
                ->pluck('tax_status', 'nip')
                ->toArray();

            $upsertData = [];
            foreach ($records as $rec) {
                $status = $fixedTaxStatuses[$rec->nip] ?? $this->service->getPTKPStatus($rec->kdstawin, $rec->janak);
                $cat = $this->service->getTERCategory($status);

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
                    $tax = $this->service->calculateMonthlyTER($bruto, $cat);
                    $calcDetails['method'] = 'TER';
                } else {
                    $prevMonths = $prevCalculationsGrouped->get($rec->nip, collect());
                    $totalGross = $prevMonths->sum('gross_base') + $bruto;
                    $totalPaid = $prevMonths->sum('tax_amount');
                    $bj = min(6000000, $totalGross * 0.05);
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
                }

                $upsertData[] = [
                    'nip' => $rec->nip,
                    'nik' => (string)($rec->noktp ?? $rec->npwp),
                    'skpd_id' => $rec->skpd_id,
                    'bulan' => $month,
                    'tahun' => $year,
                    'jenis_gaji' => 'Induk',
                    'nama' => $rec->nama,
                    'status_ptkp' => $status,
                    'kdpangkat' => $rec->kdpangkat,
                    'ter_category' => $cat,
                    'gross_base' => $bruto,
                    'tax_amount' => $tax,
                    'calc_details' => json_encode($calcDetails),
                    'updated_at' => now(),
                    'created_at' => now()
                ];
                $processed++;

                if (count($upsertData) >= 500) {
                    $this->doUpsert($upsertData);
                    $upsertData = [];
                }
            }

            if (!empty($upsertData)) {
                $this->doUpsert($upsertData);
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully processed $processed records.",
                'processed' => $processed
            ]);
        } catch (\Exception $e) {
            Log::error('PPh 21 Calculation Error: ' . $e->getMessage(), [
                'year' => $request->year,
                'month' => $request->month,
                'type' => $request->type
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghitung pajak: ' . $e->getMessage()
            ], 500);
        }
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

        $paginator = $query->selectRaw('
            c.bulan, 
            c.skpd_id,
            COALESCE(MAX(s.nama_skpd), CONCAT("SKPD ID: ", c.skpd_id)) as nama_skpd,
            COUNT(*) as total_records, 
            SUM(c.gross_base) as total_gross, 
            SUM(c.tax_amount) as total_tax
        ')
        ->groupBy('c.bulan', 'c.skpd_id')
        ->orderBy('c.bulan', 'desc')
        ->orderBy('c.skpd_id')
        ->paginate($request->per_page ?? 25);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
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

        $calcRecords = $query->select('c.*', 'm.noktp as master_nik', 'm.npwp as master_npwp')
            ->leftJoin('master_pegawai as m', 'c.nip', '=', 'm.nip')
            ->get();

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

                // Identitas: BP A2 Column F is NPWP, Column G is NIP/NRP
                // Use nik from calculation, fallback to master_nik or master_npwp
                $finalNik = $rec->nik ?: ($rec->master_nik ?: $rec->master_npwp);
                $sheet->setCellValueExplicit('F' . $row, (string)$finalNik, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('G' . $row, (string)$rec->nip, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                $sheet->setCellValue('H' . $row, $rec->status_ptkp);
                $sheet->setCellValue('I' . $row, 'Pegawai Tetap');
                $sheet->setCellValue('J' . $row, $rec->kdpangkat ?? '');
                $sheet->setCellValue('K' . $row, '21-100-01');
                $sheet->setCellValue('L' . $row, $month == 12 ? 'Biasa' : 'Biasa'); // Default status
                
                // Income mapping based on identified columns
                $sheet->setCellValue('N' . $row, $details['gaji_pokok'] ?? 0);
                $sheet->setCellValue('O' . $row, $details['tunj_istri'] ?? 0);
                $sheet->setCellValue('P' . $row, $details['tunj_anak'] ?? 0);
                $sheet->setCellValue('Q' . $row, $details['tpp'] ?? 0);
                $sheet->setCellValue('R' . $row, $details['tunj_struk_fung'] ?? 0);
                $sheet->setCellValue('S' . $row, $details['tunj_beras'] ?? 0);
                
                // Tunjangan Lain includes extra salary (THR Gaji) and extra payroll (TPP THR)
                $tunjLain = ($details['tunj_lain'] ?? 0) + ($details['extra_salary'] ?? 0) + ($details['extra_payroll'] ?? 0);
                $sheet->setCellValue('T' . $row, $tunjLain);
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

    /**
     * Get monitoring matrix (Months 1-12) for A2
     */
    public function monitoring(Request $request)
    {
        $request->validate(['year' => 'required|integer', 'skpd' => 'nullable', 'per_page' => 'nullable|integer']);
        $year = $request->year;
        $skpd = $request->skpd;
        $perPage = $request->per_page ?? 50;

        $user = auth()->user();
        $skpdIds = $user->getAccessibleSkpds();
        
        // 1. Fetch Employees (Filter out those retired for more than 1 year)
        $empQuery = DB::table('master_pegawai')
            ->where(function($q) {
                $q->whereNull('tmtstop')
                  ->orWhere('tmtstop', '>=', now()->subYear()->format('Y-m-d'));
            });

        if ($skpd) {
            // Need to resolve skpd_id to SimGaji codes for master_pegawai
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
                $kodeSkpd = DB::table('skpd')->where('id_skpd', $skpd)->value('kode_skpd');
                if ($kodeSkpd) $targetCodes = [$kodeSkpd];
            }
            $empQuery->whereIn('kdskpd', $targetCodes);
        } elseif ($skpdIds !== null) {
            $accessibleCodes = $user->getAccessibleSkpdCodes();
            if ($accessibleCodes !== null) {
                $empQuery->whereIn('kdskpd', $accessibleCodes);
            }
        }
        
        $paginator = $empQuery->select('nip', 'nama', 'noktp', 'kdskpd')->paginate($perPage);
        $employees = $paginator->items();

        // 2. Fetch Calculations
        $calcQuery = DB::table('pph21_calculations')
            ->where('tahun', $year)
            ->where('jenis_gaji', 'Induk');
            
        if ($skpd) {
            $calcQuery->where('skpd_id', $skpd);
        } elseif ($skpdIds !== null) {
            $calcQuery->whereIn('skpd_id', $skpdIds);
        }

        $calcs = $calcQuery->select('nip', 'bulan', 'skpd_id')->get()->groupBy('nip');

        // 3. SKPD names for display
        $skpdNames = DB::table('skpd')->pluck('nama_skpd', 'id_skpd')->toArray();

        // 4. Map Results
        $mappedData = collect($employees)->map(function($emp) use ($calcs, $skpdNames) {
            $myCalcs = $calcs->get($emp->nip, collect())->pluck('bulan')->toArray();
            $months = [];
            for ($m = 1; $m <= 12; $m++) {
                $months[$m] = in_array($m, $myCalcs);
            }
            
            $sampleSkpdId = $calcs->get($emp->nip, collect())->first()?->skpd_id;
            
            return [
                'nip' => $emp->nip,
                'nik' => $emp->noktp,
                'nama' => $emp->nama,
                'skpd' => $sampleSkpdId ? ($skpdNames[$sampleSkpdId] ?? 'Unknown') : 'Master Pegawai Table',
                'months' => $months
            ];
        });

        return response()->json([
            'success' => true, 
            'data' => $mappedData,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ]);
    }

    /**
     * Export Monitoring Matrix to Excel
     */
    public function exportMonitoring(Request $request)
    {
        $request->validate(['year' => 'required|integer', 'skpd' => 'nullable']);
        $year = $request->year;
        $skpd = $request->skpd;

        $user = auth()->user();
        $skpdIds = $user->getAccessibleSkpds();
        
        // 1. Fetch Employees (Filter out those retired for more than 1 year)
        $empQuery = DB::table('master_pegawai')
            ->where(function($q) {
                $q->whereNull('tmtstop')
                  ->orWhere('tmtstop', '>=', now()->subYear()->format('Y-m-d'));
            });

        if ($skpd) {
            $targetCodes = DB::table('skpd')
                ->where('id_skpd', $skpd)
                ->whereNotNull('kode_simgaji')
                ->pluck('kode_simgaji')
                ->merge(DB::table('skpd_mapping')->where('skpd_id', $skpd)->pluck('source_code'))
                ->unique()
                ->toArray();
            $empQuery->whereIn('kdskpd', $targetCodes);
        } elseif ($skpdIds !== null) {
            $accessibleCodes = $user->getAccessibleSkpdCodes();
            if ($accessibleCodes !== null) $empQuery->whereIn('kdskpd', $accessibleCodes);
        }
        
        $employees = $empQuery->select('nip', 'nama', 'noktp', 'kdskpd')->get();

        // 2. Fetch Calculations
        $calcQuery = DB::table('pph21_calculations')->where('tahun', $year)->where('jenis_gaji', 'Induk');
        if ($skpd) $calcQuery->where('skpd_id', $skpd);
        elseif ($skpdIds !== null) $calcQuery->whereIn('skpd_id', $skpdIds);
        $calcs = $calcQuery->select('nip', 'bulan', 'skpd_id')->get()->groupBy('nip');

        $skpdNames = DB::table('skpd')->pluck('nama_skpd', 'id_skpd')->toArray();
        $skpdName = $skpd ? ($skpdNames[$skpd] ?? 'Unknown') : 'SEMUA SKPD';

        // 4. Map Data for Excel
        $data = $employees->map(function($emp) use ($calcs, $skpdNames) {
            $myCalcs = $calcs->get($emp->nip, collect())->pluck('bulan')->toArray();
            $months = [];
            for ($m = 1; $m <= 12; $m++) {
                $months[$m] = in_array($m, $myCalcs);
            }
            $sampleSkpdId = $calcs->get($emp->nip, collect())->first()?->skpd_id;
            return (object)[
                'nip' => $emp->nip,
                'nik' => $emp->noktp,
                'nama' => $emp->nama,
                'skpd' => $sampleSkpdId ? ($skpdNames[$sampleSkpdId] ?? 'Unknown') : 'Master Pegawai Table',
                'months' => $months
            ];
        });

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header Style
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BDD7EE']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ];

        // Title
        $sheet->mergeCells('A1:Q1');
        $sheet->setCellValue('A1', "REKAPITULASI BUKTI POTONG A2 UNTUK PPh PASAL 21 TAHUN $year");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', "SKPD : $skpdName");
        $sheet->getStyle('A2')->getFont()->setBold(true);

        // Table Headers
        $sheet->setCellValue('A3', 'NO');
        $sheet->setCellValue('B3', 'NIK');
        $sheet->setCellValue('C3', 'NAMA');
        $sheet->setCellValue('D3', 'NIP');
        $sheet->setCellValue('E3', 'SKPD');
        
        $sheet->mergeCells('F3:Q3');
        $sheet->setCellValue('F3', 'TEMPLATE BUKTI POTONG A2 (MASA PAJAK)');
        
        $sheet->setCellValue('F4', '1');
        $sheet->setCellValue('G4', '2');
        $sheet->setCellValue('H4', '3');
        $sheet->setCellValue('I4', '4');
        $sheet->setCellValue('J4', '5');
        $sheet->setCellValue('K4', '6');
        $sheet->setCellValue('L4', '7');
        $sheet->setCellValue('M4', '8');
        $sheet->setCellValue('N4', '9');
        $sheet->setCellValue('O4', '10');
        $sheet->setCellValue('P4', '11');
        $sheet->setCellValue('Q4', '12');

        $sheet->mergeCells('A3:A4');
        $sheet->mergeCells('B3:B4');
        $sheet->mergeCells('C3:C4');
        $sheet->mergeCells('D3:D4');
        $sheet->mergeCells('E3:E4');

        $sheet->getStyle('A3:Q4')->applyFromArray($headerStyle);

        // Data
        $row = 5;
        foreach ($data as $idx => $item) {
            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValueExplicit('B' . $row, (string)$item->nik, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, $item->nama);
            $sheet->setCellValueExplicit('D' . $row, (string)$item->nip, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('E' . $row, $item->skpd);
            
            $col = 'F';
            foreach ($item->months as $m => $exists) {
                $val = $exists ? 'Ada' : 'Tidak Ada';
                $sheet->setCellValue($col . $row, $val);
                if (!$exists) {
                    $sheet->getStyle($col . $row)->getFont()->getColor()->setRGB('FF0000');
                }
                $col++;
            }
            
            $sheet->getStyle('A' . $row . ':Q' . $row)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $row++;
        }

        // Auto size
        foreach (range('A', 'Q') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $periodLabel = $skpd ? "SKPD_{$skpd}" : "SEMUA";
        $fileName = "Monitoring_A2_{$year}_{$periodLabel}.xlsx";
        $tempFile = tempnam(sys_get_temp_dir(), 'pph21_mon');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
