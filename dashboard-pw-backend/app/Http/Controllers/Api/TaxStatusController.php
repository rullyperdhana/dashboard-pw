<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxStatus;
use App\Models\MasterPegawai;
use App\Models\PegawaiPw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TaxStatusExport;
use App\Imports\TaxStatusImport;

class TaxStatusController extends Controller
{
    private function buildPlaceholders($array) {
        return "'" . implode("','", $array) . "'";
    }

    public function index(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'type' => 'nullable|in:pns,pppk',
        ]);

        $query = TaxStatus::query()
            ->leftJoin('master_pegawai', 'tax_statuses.nip', '=', 'master_pegawai.nip')
            ->leftJoin('skpd as skpd_pns', 'master_pegawai.kdskpd', '=', DB::raw('skpd_pns.kode_skpd COLLATE utf8mb4_unicode_ci'))
            ->leftJoin('pegawai_pw', 'tax_statuses.nip', '=', 'pegawai_pw.nip')
            ->leftJoin('skpd as skpd_pppk', 'pegawai_pw.idskpd', '=', 'skpd_pppk.id_skpd')
            ->select('tax_statuses.*')
            ->addSelect(DB::raw('COALESCE(skpd_pns.nama_skpd, skpd_pppk.nama_skpd) as skpd_name'))
            ->where('tax_statuses.year', $request->year);

        if ($request->type) {
            $query->where('tax_statuses.employee_type', $request->type);
        }

        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            $skpdIds = $user->getAccessibleSkpds();
            $skpdCodes = $user->getAccessibleSkpdCodes();
            
            $query->where(function($q) use ($skpdIds, $skpdCodes) {
                $q->whereIn('tax_statuses.nip', function($sq) use ($skpdCodes) {
                    $sq->select('nip')->from('master_pegawai')->whereIn('kdskpd', $skpdCodes);
                })
                ->orWhereIn('tax_statuses.nip', function($sq) use ($skpdIds) {
                    $sq->select('nip')->from('pegawai_pw')->whereIn('idskpd', $skpdIds);
                });
            });
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('tax_statuses.nip', 'like', "%$s%")
                    ->orWhere('tax_statuses.nama', 'like', "%$s%")
                    ->orWhere('tax_statuses.tax_status', 'like', "%$s%");
            });
        }

        $data = $query->orderBy('tax_statuses.nama')->paginate($request->per_page ?? 15);

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nip' => 'required|string',
            'nama' => 'required|string',
            'employee_type' => 'required|in:pns,pppk',
            'tax_status' => 'required|string|max:10',
            'year' => 'required|integer',
        ]);

        $taxStatus = TaxStatus::updateOrCreate(
            ['nip' => $request->nip, 'year' => $request->year],
            [
                'nama' => $request->nama,
                'employee_type' => $request->employee_type,
                'tax_status' => strtoupper($request->tax_status),
                'is_manual' => true
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Status pajak berhasil diperbarui',
            'data' => $taxStatus
        ]);
    }

    public function initializeYear(Request $request)
    {
        $request->validate([
            'source_year' => 'nullable|integer',
            'target_year' => 'required|integer',
        ]);

        $targetYear = $request->target_year;
        $sourceYear = $request->source_year;
        $count = 0;

        // 1. If source_year exists, copy from previous year (only if not exists in target)
        if ($sourceYear) {
            $prevData = TaxStatus::where('year', $sourceYear)->get();
            foreach ($prevData as $prev) {
                $exists = TaxStatus::where('nip', $prev->nip)->where('year', $targetYear)->exists();
                if (!$exists) {
                    TaxStatus::create([
                        'nip' => $prev->nip,
                        'nama' => $prev->nama,
                        'employee_type' => $prev->employee_type,
                        'tax_status' => $prev->tax_status,
                        'year' => $targetYear,
                        'is_manual' => false
                    ]);
                    $count++;
                }
            }
        }

        // 2. Also check MasterPegawai (PNS) and PegawaiPw (PPPK) for any missing employees
        $pns = DB::table('master_pegawai')->select('nip', 'nama', 'kdstawin', 'janak', 'kdjenkel')->get();
        foreach ($pns as $p) {
            if (!TaxStatus::where('nip', $p->nip)->where('year', $targetYear)->exists()) {
                $isFemale = ($p->kdjenkel == 2 || (strlen($p->nip) >= 15 && substr($p->nip, 14, 1) == '2'));

                if ($isFemale) {
                    $taxStatusValue = 'TK/0';
                } else {
                    $statusLetter = ($p->kdstawin == 2) ? 'K' : 'TK';
                    $childCount = $p->janak ?: 0;
                    if ($childCount > 3)
                        $childCount = 3; // PTKP Max 3
                    $taxStatusValue = "$statusLetter/$childCount";
                }

                TaxStatus::create([
                    'nip' => $p->nip,
                    'nama' => $p->nama,
                    'employee_type' => 'pns',
                    'tax_status' => $taxStatusValue,
                    'year' => $targetYear,
                    'is_manual' => false
                ]);
                $count++;
            }
        }

        $pppk = DB::table('pegawai_pw')->select('nip', 'nama')->get();
        foreach ($pppk as $p) {
            if (!TaxStatus::where('nip', $p->nip)->where('year', $targetYear)->exists()) {
                // For PPPK, if we don't have family fields yet, we'll keep as TK/0 or -
                TaxStatus::create([
                    'nip' => $p->nip,
                    'nama' => $p->nama,
                    'employee_type' => 'pppk',
                    'tax_status' => 'TK/0',
                    'year' => $targetYear,
                    'is_manual' => false
                ]);
                $count++;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil inisialisasi $count data pegawai untuk tahun $targetYear",
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'type' => 'nullable|in:pns,pppk',
        ]);

        $fileName = "status_pajak_{$request->year}.xlsx";
        return Excel::download(new TaxStatusExport($request->year, $request->type), $fileName);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'year' => 'required|integer',
        ]);

        try {
            Excel::import(new TaxStatusImport($request->year), $request->file('file'));

            return response()->json([
                'status' => 'success',
                'message' => 'Data status pajak berhasil diimpor',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengimpor data: ' . $e->getMessage(),
            ], 500);
        }
    }
}
