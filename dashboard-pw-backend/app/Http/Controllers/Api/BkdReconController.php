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
        $filter = $request->query('filter', 'all'); // all, diff, bkd_only, simgaji_only
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

        // Also get SimGaji-only records
        $simgajiOnly = DB::table('master_pegawai as m')
            ->leftJoin('bkd_pegawai as b', 'm.nip', '=', 'b.nip')
            ->whereNull('b.nip')
            ->whereIn('m.kdstapeg', [1, 2, 3, 4, 5, 11, 12]) // Active employees
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
            $simgajiOnly->where(function($q) use ($search) {
                $q->where('m.nip', 'LIKE', "%{$search}%")
                  ->orWhere('m.nama', 'LIKE', "%{$search}%");
            });
        }

        // Apply filter
        if ($filter === 'bkd_only') {
            $query->whereNull('m.nip');
            $paginated = $query->paginate($perPage);
        } elseif ($filter === 'simgaji_only') {
            $paginated = $simgajiOnly->paginate($perPage);
        } elseif ($filter === 'diff') {
            $query->whereNotNull('m.nip');
            // We'll post-process to filter only rows with differences
            $allMatched = $query->get();
            $diffRows = $allMatched->filter(function($row) {
                return $this->hasDifferences($row);
            })->values();
            
            // Manual pagination
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

        // Matched NIP count
        $matchedCount = DB::table('bkd_pegawai as b')
            ->join('master_pegawai as m', 'b.nip', '=', 'm.nip')
            ->count();

        // BKD only (not in SimGaji)
        $bkdOnlyCount = DB::table('bkd_pegawai as b')
            ->leftJoin('master_pegawai as m', 'b.nip', '=', 'm.nip')
            ->whereNull('m.nip')
            ->count();

        // SimGaji only (active, not in BKD)
        $simgajiOnlyCount = DB::table('master_pegawai as m')
            ->leftJoin('bkd_pegawai as b', 'm.nip', '=', 'b.nip')
            ->whereNull('b.nip')
            ->whereIn('m.kdstapeg', [1, 2, 3, 4, 5, 11, 12])
            ->count();

        // Count with differences (from matched)
        $matchedRows = DB::table('bkd_pegawai as b')
            ->join('master_pegawai as m', 'b.nip', '=', 'm.nip')
            ->select(
                'b.nama as bkd_nama', 'b.nik as bkd_nik', 'b.golongan as bkd_golongan',
                'm.nama as sg_nama', 'm.noktp as sg_nik', 'm.kdpangkat as sg_golongan'
            )->get();

        $diffCount = $matchedRows->filter(function($row) {
            return $this->hasDifferences($row);
        })->count();

        $identicalCount = $matchedCount - $diffCount;

        return response()->json([
            'success' => true,
            'data' => [
                'bkd_total' => $bkdCount,
                'simgaji_active_total' => $simgajiActiveCount,
                'matched' => $matchedCount,
                'identical' => $identicalCount,
                'with_differences' => $diffCount,
                'bkd_only' => $bkdOnlyCount,
                'simgaji_only' => $simgajiOnlyCount,
                'last_upload' => BkdPegawai::max('created_at'),
            ]
        ]);
    }

    /**
     * Check if a matched row has differences
     */
    private function hasDifferences($row): bool
    {
        // Compare Nama (case-insensitive, trim)
        $bkdNama = strtoupper(trim($row->bkd_nama ?? ''));
        $sgNama = strtoupper(trim($row->sg_nama ?? ''));
        if ($bkdNama && $sgNama && $bkdNama !== $sgNama) return true;

        // Compare NIK
        $bkdNik = trim(str_replace("'", '', $row->bkd_nik ?? ''));
        $sgNik = trim($row->sg_nik ?? '');
        if ($bkdNik && $sgNik && $bkdNik !== $sgNik) return true;

        // Compare Golongan (extract roman numeral + letter from both)
        $bkdGol = $this->normalizeGolongan($row->bkd_golongan ?? '');
        $sgGol = $this->normalizeGolongan($row->sg_golongan ?? '');
        if ($bkdGol && $sgGol && $bkdGol !== $sgGol) return true;

        return false;
    }

    /**
     * Normalize golongan string for comparison.
     * Handles formats like "IV/e", "4e", "IVe", etc.
     */
    private function normalizeGolongan(string $gol): string
    {
        $gol = strtoupper(trim($gol));
        if (!$gol) return '';

        // Remove separators
        $gol = str_replace(['/', ' ', '-'], '', $gol);
        return $gol;
    }

    /**
     * Format a row for JSON output, annotating differences
     */
    private function formatRow($row): array
    {
        $result = (array) $row;

        // Determine differences per field
        $diffs = [];
        
        $bkdNama = strtoupper(trim($row->bkd_nama ?? ''));
        $sgNama = strtoupper(trim($row->sg_nama ?? ''));
        if ($bkdNama && $sgNama && $bkdNama !== $sgNama) $diffs[] = 'nama';

        $bkdNik = trim(str_replace("'", '', $row->bkd_nik ?? ''));
        $sgNik = trim($row->sg_nik ?? '');
        if ($bkdNik && $sgNik && $bkdNik !== $sgNik) $diffs[] = 'nik';

        $bkdGol = $this->normalizeGolongan($row->bkd_golongan ?? '');
        $sgGol = $this->normalizeGolongan($row->sg_golongan ?? '');
        if ($bkdGol && $sgGol && $bkdGol !== $sgGol) $diffs[] = 'golongan';

        $result['differences'] = $diffs;
        $result['has_diff'] = !empty($diffs);

        return $result;
    }
}
