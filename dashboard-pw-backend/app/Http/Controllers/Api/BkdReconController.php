<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\BkdImport;
use App\Models\BkdPegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class BkdReconController extends Controller
{
    /**
     * Upload data BKD dari file Excel
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        // Clear old data
        BkdPegawai::truncate();

        $batch = now()->format('YmdHis');
        Excel::import(new BkdImport($batch), $request->file('file'));

        $count = BkdPegawai::where('upload_batch', $batch)->count();

        return response()->json([
            'success' => true,
            'message' => "Berhasil mengimpor {$count} data pegawai BKD.",
            'count' => $count,
        ]);
    }

    /**
     * Get reconciliation comparison data
     */
    public function index(Request $request)
    {
        // filter: all, diff, diff_nik, diff_golongan, diff_jabatan, bkd_only, simgaji_only
        $filter = $request->query('filter', 'all');
        $search = $request->query('search');
        $perPage = $request->query('per_page', 50);

        $bkdCount = BkdPegawai::count();
        if ($bkdCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada data BKD yang diupload. Silakan upload file data_bkd.xlsx terlebih dahulu.'
            ]);
        }

        // LEFT JOIN bkd_pegawai with master_pegawai on NIP
        $query = $this->buildBkdQuery($search);

        // SimGaji-only records
        $simgajiOnly = $this->buildSimgajiOnlyQuery($search);

        // Apply filter
        if ($filter === 'bkd_only') {
            $query->whereNull('m.nip');
            $paginated = $query->paginate($perPage);
        } elseif ($filter === 'simgaji_only') {
            $paginated = $simgajiOnly->paginate($perPage);
        } elseif (str_starts_with($filter, 'diff')) {
            $query->whereNotNull('m.nip');
            $allMatched = $query->get();
            
            $diffType = $filter === 'diff' ? null : str_replace('diff_', '', $filter);
            
            $diffRows = $allMatched->filter(function($row) use ($diffType) {
                if ($diffType) {
                    $diffs = $this->getDifferences($row);
                    return in_array($diffType, $diffs);
                }
                return $this->hasDifferences($row);
            })->values();
            
            $page = $request->query('page', 1);
            $sliced = $diffRows->slice(($page - 1) * $perPage, $perPage)->values();
            
            return response()->json([
                'success' => true,
                'data' => $sliced->map(fn($r) => $this->formatRow($r)),
                'meta' => [
                    'current_page' => (int)$page,
                    'last_page' => max(1, ceil($diffRows->count() / $perPage)),
                    'per_page' => $perPage,
                    'total' => $diffRows->count(),
                ]
            ]);
        } else {
            // All: union both queries
            $combined = $query->unionAll($simgajiOnly);
            $paginated = DB::table(DB::raw("({$combined->toSql()}) as combined"))
                ->mergeBindings($combined)
                ->paginate($perPage);
        }

        return response()->json([
            'success' => true,
            'data' => collect($paginated->items())->map(fn($r) => $this->formatRow($r)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ]
        ]);
    }

    /**
     * Get summary statistics
     */
    public function summary()
    {
        $bkdCount = BkdPegawai::count();
        if ($bkdCount === 0) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Belum ada data BKD.'
            ]);
        }

        $simgajiActiveCount = DB::table('master_pegawai')
            ->whereIn('kdstapeg', [1, 2, 3, 4, 5, 11, 12])
            ->count();

        $matchedCount = DB::table('bkd_pegawai as b')
            ->join('master_pegawai as m', 'b.nip', '=', 'm.nip')
            ->count();

        $bkdOnlyCount = DB::table('bkd_pegawai as b')
            ->leftJoin('master_pegawai as m', 'b.nip', '=', 'm.nip')
            ->whereNull('m.nip')
            ->count();

        $simgajiOnlyCount = DB::table('master_pegawai as m')
            ->leftJoin('bkd_pegawai as b', 'm.nip', '=', 'b.nip')
            ->whereNull('b.nip')
            ->whereIn('m.kdstapeg', [1, 2, 3, 4, 5, 11, 12])
            ->count();

        // Count differences by type
        $matchedRows = DB::table('bkd_pegawai as b')
            ->join('master_pegawai as m', 'b.nip', '=', 'm.nip')
            ->select(
                'b.nik as bkd_nik', 'b.golongan as bkd_golongan', 'b.jabatan as bkd_jabatan',
                'm.noktp as sg_nik', 'm.kdpangkat as sg_golongan'
            )->get();

        $diffNik = 0;
        $diffGol = 0;
        $diffJabatan = 0;
        $anyDiff = 0;

        foreach ($matchedRows as $row) {
            $diffs = $this->getDifferences($row);
            if (!empty($diffs)) $anyDiff++;
            if (in_array('nik', $diffs)) $diffNik++;
            if (in_array('golongan', $diffs)) $diffGol++;
            if (in_array('jabatan', $diffs)) $diffJabatan++;
        }

        $identicalCount = $matchedCount - $anyDiff;

        return response()->json([
            'success' => true,
            'data' => [
                'bkd_total' => $bkdCount,
                'simgaji_active_total' => $simgajiActiveCount,
                'matched' => $matchedCount,
                'identical' => $identicalCount,
                'with_differences' => $anyDiff,
                'diff_nik' => $diffNik,
                'diff_golongan' => $diffGol,
                'diff_jabatan' => $diffJabatan,
                'bkd_only' => $bkdOnlyCount,
                'simgaji_only' => $simgajiOnlyCount,
                'last_upload' => BkdPegawai::max('created_at'),
            ]
        ]);
    }

    /**
     * Export reconciliation to XLS for manual checking
     */
    public function export(Request $request)
    {
        $filter = $request->query('filter', 'all');

        $query = $this->buildBkdQuery(null);
        $simgajiOnly = $this->buildSimgajiOnlyQuery(null);

        if ($filter === 'bkd_only') {
            $query->whereNull('m.nip');
            $rows = $query->get();
        } elseif ($filter === 'simgaji_only') {
            $rows = $simgajiOnly->get();
        } elseif (str_starts_with($filter, 'diff')) {
            $query->whereNotNull('m.nip');
            $allMatched = $query->get();
            $diffType = $filter === 'diff' ? null : str_replace('diff_', '', $filter);
            $rows = $allMatched->filter(function($row) use ($diffType) {
                if ($diffType) {
                    return in_array($diffType, $this->getDifferences($row));
                }
                return $this->hasDifferences($row);
            })->values();
        } else {
            $combined = $query->unionAll($simgajiOnly);
            $rows = DB::table(DB::raw("({$combined->toSql()}) as combined"))
                ->mergeBindings($combined)
                ->get();
        }

        // Build spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekon BKD');

        // Header
        $headers = ['No', 'NIP', 'Nama (BKD)', 'Nama (SimGaji)', 'NIK (BKD)', 'NIK (SimGaji)', 'Gol (BKD)', 'Gol (SimGaji)', 'Jabatan (BKD)', 'Status', 'Selisih'];
        foreach ($headers as $col => $h) {
            $sheet->setCellValue([$col + 1, 1], $h);
        }

        // Style header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

        // Data rows
        $rowNum = 2;
        foreach ($rows as $i => $row) {
            $formatted = $this->formatRow($row);
            $statusLabel = $this->getStatusLabel($formatted);
            $diffLabel = implode(', ', $formatted['differences'] ?? []);

            $sheet->setCellValue([1, $rowNum], $i + 1);
            $sheet->setCellValueExplicit([2, $rowNum], $row->nip, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue([3, $rowNum], $row->bkd_nama ?? '-');
            $sheet->setCellValue([4, $rowNum], $row->sg_nama ?? '-');
            $sheet->setCellValueExplicit([5, $rowNum], str_replace("'", '', $row->bkd_nik ?? '-'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit([6, $rowNum], $row->sg_nik ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue([7, $rowNum], $row->bkd_golongan ?? '-');
            $sheet->setCellValue([8, $rowNum], $row->sg_golongan ?? '-');
            $sheet->setCellValue([9, $rowNum], $row->bkd_jabatan ?? '-');
            $sheet->setCellValue([10, $rowNum], $statusLabel);
            $sheet->setCellValue([11, $rowNum], $diffLabel);

            // Color code diffs
            if (!empty($formatted['differences'])) {
                $sheet->getStyle("A{$rowNum}:K{$rowNum}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFF2CC');
            }
            if (($formatted['match_status'] ?? '') === 'bkd_only') {
                $sheet->getStyle("A{$rowNum}:K{$rowNum}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FCE4D6');
            }
            if (($formatted['match_status'] ?? '') === 'simgaji_only') {
                $sheet->getStyle("A{$rowNum}:K{$rowNum}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8D7DA');
            }

            $rowNum++;
        }

        // Auto column width
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add border to all data
        $sheet->getStyle("A1:K" . ($rowNum - 1))->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $fileName = 'rekon_bkd_' . now()->format('Ymd_His') . '.xlsx';
        $tempPath = storage_path("app/{$fileName}");

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }

    // ─── Private Helpers ───────────────────────────────────────────

    private function buildBkdQuery($search)
    {
        $query = DB::table('bkd_pegawai as b')
            ->leftJoin('master_pegawai as m', 'b.nip', '=', 'm.nip')
            ->select(
                'b.nip',
                'b.nama as bkd_nama',
                'b.nik as bkd_nik',
                'b.jabatan as bkd_jabatan',
                'b.golongan as bkd_golongan',
                'b.tgl_lahir as bkd_tgl_lahir',
                'b.jenis_kelamin as bkd_jk',
                'm.nama as sg_nama',
                'm.noktp as sg_nik',
                DB::raw("CONCAT(m.glrdepan, ' ', m.nama, ' ', COALESCE(m.glrbelakan, '')) as sg_nama_lengkap"),
                'm.kdpangkat as sg_golongan',
                'm.tgllhr as sg_tgl_lahir',
                'm.kdjenkel as sg_jk',
                DB::raw("CASE WHEN m.nip IS NULL THEN 'bkd_only' ELSE 'matched' END as match_status")
            );

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('b.nip', 'LIKE', "%{$search}%")
                  ->orWhere('b.nama', 'LIKE', "%{$search}%")
                  ->orWhere('m.nama', 'LIKE', "%{$search}%");
            });
        }

        return $query;
    }

    private function buildSimgajiOnlyQuery($search)
    {
        $query = DB::table('master_pegawai as m')
            ->leftJoin('bkd_pegawai as b', 'm.nip', '=', 'b.nip')
            ->whereNull('b.nip')
            ->whereIn('m.kdstapeg', [1, 2, 3, 4, 5, 11, 12])
            ->select(
                'm.nip',
                DB::raw("NULL as bkd_nama"),
                DB::raw("NULL as bkd_nik"),
                DB::raw("NULL as bkd_jabatan"),
                DB::raw("NULL as bkd_golongan"),
                DB::raw("NULL as bkd_tgl_lahir"),
                DB::raw("NULL as bkd_jk"),
                'm.nama as sg_nama',
                'm.noktp as sg_nik',
                DB::raw("CONCAT(m.glrdepan, ' ', m.nama, ' ', COALESCE(m.glrbelakan, '')) as sg_nama_lengkap"),
                'm.kdpangkat as sg_golongan',
                'm.tgllhr as sg_tgl_lahir',
                'm.kdjenkel as sg_jk',
                DB::raw("'simgaji_only' as match_status")
            );

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('m.nip', 'LIKE', "%{$search}%")
                  ->orWhere('m.nama', 'LIKE', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Get list of difference types for a row
     */
    private function getDifferences($row): array
    {
        $diffs = [];

        // NIK
        $bkdNik = trim(str_replace("'", '', $row->bkd_nik ?? ''));
        $sgNik = trim($row->sg_nik ?? '');
        if ($bkdNik && $sgNik && $bkdNik !== $sgNik) $diffs[] = 'nik';

        // Golongan
        $bkdGol = $this->normalizeGolongan($row->bkd_golongan ?? '');
        $sgGol = $this->normalizeGolongan($row->sg_golongan ?? '');
        if ($bkdGol && $sgGol && $bkdGol !== $sgGol) $diffs[] = 'golongan';

        // Jabatan: only flag if BKD jabatan is empty or filled but there's a mismatch indicator
        // Since SimGaji doesn't have jabatan text, we just display BKD jabatan for now
        // We flag jabatan issues when BKD jabatan is missing for a matched record
        $bkdJabatan = trim($row->bkd_jabatan ?? '');
        if (isset($row->match_status) && $row->match_status === 'matched' && empty($bkdJabatan)) {
            $diffs[] = 'jabatan';
        }

        return $diffs;
    }

    private function hasDifferences($row): bool
    {
        return !empty($this->getDifferences($row));
    }

    /**
     * Normalize golongan to unified numeric format.
     * "III/d" -> "3D", "3D" -> "3D", "IV/e" -> "4E", "IX" -> "9", "09" -> "9"
     */
    private function normalizeGolongan(string $gol): string
    {
        $gol = strtoupper(trim($gol));
        if (!$gol) return '';

        $gol = str_replace(['/', ' ', '-', '.'], '', $gol);

        // Map Roman numerals to numbers (longest first to avoid partial match)
        $romanMap = [
            'VIII' => '8', 'VII' => '7', 'VI' => '6',
            'IV' => '4', 'IX' => '9', 'V' => '5',
            'III' => '3', 'II' => '2', 'X' => '10', 'I' => '1',
        ];

        foreach ($romanMap as $roman => $number) {
            if (str_starts_with($gol, $roman)) {
                $gol = $number . substr($gol, strlen($roman));
                break;
            }
        }

        // Strip leading zeros from numeric part (e.g. "09" -> "9")
        $gol = ltrim($gol, '0') ?: '0';

        return $gol;
    }

    private function formatRow($row): array
    {
        $result = (array) $row;
        $result['differences'] = $this->getDifferences($row);
        $result['has_diff'] = !empty($result['differences']);
        return $result;
    }

    private function getStatusLabel($formatted): string
    {
        $status = $formatted['match_status'] ?? '';
        if ($status === 'bkd_only') return 'BKD Only';
        if ($status === 'simgaji_only') return 'SG Only';
        if ($formatted['has_diff'] ?? false) return 'Selisih';
        return 'Identik';
    }
}
