<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Exports\BpjsRekonSkpdExport;
use App\Exports\BpjsRekonJabatanExport;
use App\Exports\BpjsRekonRekeningExport;
use App\Exports\BpjsRekonSkpdRekeningExport;
use App\Exports\BpjsRekonDetailExport;
use Maatwebsite\Excel\Facades\Excel;

class BpjsRekonController extends Controller
{
    /**
     * BPJS 4% Reconciliation Report for PPPK Paruh Waktu.
     *
     * Formula:
     * - Jika gaji_pokok >= UMP → BPJS 4% = gaji_pokok × 4%
     * - Jika gaji_pokok <  UMP → BPJS 4% = UMP × 4% (fixed)
     */
    public function index(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;
        $sumberDana = $request->input('sumber_dana');
        $ump = (float) Setting::getValue('ump_kalsel', 3725000);
        $bpjsUmp = round($ump * 0.04, 0); // BPJS 4% dari UMP

        // Get payment detail with employee info
        $query = DB::table('tb_payment_detail')
            ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
            ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
            ->where('tb_payment.month', $month)
            ->where('tb_payment.year', $year);

        if ($sumberDana && $sumberDana !== 'Semua') {
            $query->where('pegawai_pw.sumber_dana', $sumberDana);
        }

        $data = $query->select(
            'pegawai_pw.nip',
            'pegawai_pw.nama',
            'pegawai_pw.skpd',
            'pegawai_pw.upt',
            'pegawai_pw.jabatan',
            'pegawai_pw.sumber_dana',
            'tb_payment_detail.gaji_pokok',
            'tb_payment_detail.total_amoun',
            DB::raw("CASE 
                    WHEN tb_payment_detail.gaji_pokok < {$ump} THEN {$bpjsUmp}
                    ELSE ROUND(tb_payment_detail.gaji_pokok * 0.04, 0) 
                END as bpjs_4_persen"),
            DB::raw("CASE 
                    WHEN tb_payment_detail.gaji_pokok < {$ump} THEN 'UMP'
                    ELSE 'GAJI' 
                END as basis_hitung")
        )
            ->orderBy('pegawai_pw.skpd')
            ->orderBy('pegawai_pw.nama')
            ->get();

        // Summary per SKPD
        $skpdQuery = DB::table('tb_payment_detail')
            ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
            ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
            ->where('tb_payment.month', $month)
            ->where('tb_payment.year', $year);

        if ($sumberDana && $sumberDana !== 'Semua') {
            $skpdQuery->where('pegawai_pw.sumber_dana', $sumberDana);
        }

        $skpdSummary = $skpdQuery->select(
            'pegawai_pw.skpd',
            DB::raw('COUNT(*) as jumlah_pegawai'),
            DB::raw('SUM(tb_payment_detail.gaji_pokok) as total_gaji_pokok'),
            DB::raw("SUM(CASE 
                    WHEN tb_payment_detail.gaji_pokok < {$ump} THEN {$bpjsUmp}
                    ELSE ROUND(tb_payment_detail.gaji_pokok * 0.04, 0) 
                END) as total_bpjs_4_persen"),
            DB::raw('SUM(tb_payment_detail.total_amoun) as total_gaji_bersih'),
            DB::raw("SUM(CASE WHEN tb_payment_detail.gaji_pokok < {$ump} THEN 1 ELSE 0 END) as pegawai_bawah_ump")
        )
            ->groupBy('pegawai_pw.skpd')
            ->orderBy('pegawai_pw.skpd')
            ->get();

        // Summary per Jabatan
        $jabatanQuery = DB::table('tb_payment_detail')
            ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
            ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
            ->where('tb_payment.month', $month)
            ->where('tb_payment.year', $year);

        if ($sumberDana && $sumberDana !== 'Semua') {
            $jabatanQuery->where('pegawai_pw.sumber_dana', $sumberDana);
        }

        $jabatanSummary = $jabatanQuery->select(
            'pegawai_pw.jabatan',
            DB::raw('COUNT(*) as jumlah_pegawai'),
            DB::raw('SUM(tb_payment_detail.gaji_pokok) as total_gaji_pokok'),
            DB::raw("SUM(CASE 
                    WHEN tb_payment_detail.gaji_pokok < {$ump} THEN {$bpjsUmp}
                    ELSE ROUND(tb_payment_detail.gaji_pokok * 0.04, 0) 
                END) as total_bpjs_4_persen"),
            DB::raw('SUM(tb_payment_detail.total_amoun) as total_gaji_bersih'),
            DB::raw("SUM(CASE WHEN tb_payment_detail.gaji_pokok < {$ump} THEN 1 ELSE 0 END) as pegawai_bawah_ump")
        )
            ->groupBy('pegawai_pw.jabatan')
            ->orderBy('pegawai_pw.jabatan')
            ->get();

        // Summary per Rekening (Mapping)
        $mappings = DB::table('pppk_pw_jabatan_mappings')->orderBy('order_weight', 'desc')->get();
        $rekeningSummary = [];

        if ($mappings->isNotEmpty()) {
            $caseSql = "CASE ";
            foreach ($mappings as $m) {
                $caseSql .= "WHEN pw.jabatan LIKE '%" . addslashes($m->keyword) . "%' THEN " . $m->id . " ";
            }
            $caseSql .= "ELSE 0 END as mapping_id";

            $rekeningSummary = DB::select("
                SELECT 
                    mapped.mapping_id,
                    COALESCE(m.nama_kelompok, 'Lainnya') as nama_kelompok,
                    COALESCE(m.kode_rekening, '-') as kode_rekening,
                    COUNT(*) as jumlah_pegawai,
                    SUM(mapped.gaji_pokok) as total_gaji_pokok,
                    SUM(mapped.bpjs_4_persen) as total_bpjs_4_persen,
                    SUM(mapped.total_amoun) as total_gaji_bersih,
                    SUM(CASE WHEN mapped.gaji_pokok < ? THEN 1 ELSE 0 END) as pegawai_bawah_ump
                FROM (
                    SELECT 
                        pw.jabatan, pd.gaji_pokok, pd.total_amoun,
                        CASE 
                            WHEN pd.gaji_pokok < ? THEN ?
                            ELSE ROUND(pd.gaji_pokok * 0.04, 0) 
                        END as bpjs_4_persen,
                        $caseSql
                    FROM tb_payment_detail pd
                    JOIN pegawai_pw pw ON pd.employee_id = pw.id
                    JOIN tb_payment p  ON pd.payment_id = p.id
                    WHERE p.month = ? AND p.year = ? " . ($sumberDana && $sumberDana !== 'Semua' ? "AND pw.sumber_dana = " . DB::getPdo()->quote($sumberDana) : "") . "
                ) as mapped
                LEFT JOIN pppk_pw_jabatan_mappings m ON mapped.mapping_id = m.id
                GROUP BY mapped.mapping_id, m.nama_kelompok, m.kode_rekening, m.order_weight
                ORDER BY m.order_weight DESC, m.nama_kelompok ASC
            ", [$ump, $ump, $bpjsUmp, $month, $year]);
        }

        // Summary per SKPD & Rekening (Mapping)
        $skpdRekeningSummary = [];
        if ($mappings->isNotEmpty()) {
            $caseSql = "CASE ";
            foreach ($mappings as $m) {
                $caseSql .= "WHEN pw.jabatan LIKE '%" . addslashes($m->keyword) . "%' THEN " . $m->id . " ";
            }
            $caseSql .= "ELSE 0 END as mapping_id";

            $skpdRekeningSummary = DB::select("
                SELECT 
                    mapped.skpd,
                    mapped.mapping_id,
                    COALESCE(m.nama_kelompok, 'Lainnya') as nama_kelompok,
                    COALESCE(m.kode_rekening, '-') as kode_rekening,
                    COUNT(*) as jumlah_pegawai,
                    SUM(mapped.gaji_pokok) as total_gaji_pokok,
                    SUM(mapped.bpjs_4_persen) as total_bpjs_4_persen,
                    SUM(mapped.total_amoun) as total_gaji_bersih,
                    SUM(CASE WHEN mapped.gaji_pokok < ? THEN 1 ELSE 0 END) as pegawai_bawah_ump
                FROM (
                    SELECT 
                        pw.skpd, pw.jabatan, pd.gaji_pokok, pd.total_amoun,
                        CASE 
                            WHEN pd.gaji_pokok < ? THEN ?
                            ELSE ROUND(pd.gaji_pokok * 0.04, 0) 
                        END as bpjs_4_persen,
                        $caseSql
                    FROM tb_payment_detail pd
                    JOIN pegawai_pw pw ON pd.employee_id = pw.id
                    JOIN tb_payment p  ON pd.payment_id = p.id
                    WHERE p.month = ? AND p.year = ? " . ($sumberDana && $sumberDana !== 'Semua' ? "AND pw.sumber_dana = " . DB::getPdo()->quote($sumberDana) : "") . "
                ) as mapped
                LEFT JOIN pppk_pw_jabatan_mappings m ON mapped.mapping_id = m.id
                GROUP BY mapped.skpd, mapped.mapping_id, m.nama_kelompok, m.kode_rekening, m.order_weight
                ORDER BY mapped.skpd ASC, m.order_weight DESC
            ", [$ump, $ump, $bpjsUmp, $month, $year]);
        }

        // Grand total
        $grandTotal = [
            'jumlah_pegawai' => $data->count(),
            'total_gaji_pokok' => $data->sum('gaji_pokok'),
            'total_bpjs_4_persen' => $data->sum('bpjs_4_persen'),
            'total_gaji_bersih' => $data->sum('total_amoun'),
            'pegawai_bawah_ump' => $data->where('basis_hitung', 'UMP')->count(),
            'pegawai_atas_ump' => $data->where('basis_hitung', 'GAJI')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'detail' => $data,
                'skpd_summary' => $skpdSummary,
                'jabatan_summary' => $jabatanSummary,
                'rekening_summary' => $rekeningSummary,
                'skpd_rekening_summary' => $skpdRekeningSummary,
                'grand_total' => $grandTotal,
                'period' => [
                    'month' => $month,
                    'year' => $year,
                ],
                'ump' => $ump,
                'bpjs_ump' => $bpjsUmp,
            ],
        ]);
    }

    /**
     * Export BPJS Rekon to Excel.
     */
    public function export(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
            'type' => 'required|in:skpd,detail,jabatan,rekening,skpd-rekening',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;
        $type = $request->type;
        $sumberDana = $request->input('sumber_dana');
        $ump = (float) Setting::getValue('ump_kalsel', 3725000);
        $bpjsUmp = round($ump * 0.04, 0);

        // Sub-query logic duplicated for simplicity in export
        $query = DB::table('tb_payment_detail')
            ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
            ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
            ->where('tb_payment.month', $month)
            ->where('tb_payment.year', $year);

        if ($sumberDana && $sumberDana !== 'Semua') {
            $query->where('pegawai_pw.sumber_dana', $sumberDana);
        }

        if ($type === 'skpd') {
            $summary = $query->select(
                'pegawai_pw.skpd',
                DB::raw('COUNT(*) as jumlah_pegawai'),
                DB::raw('SUM(tb_payment_detail.gaji_pokok) as total_gaji_pokok'),
                DB::raw("SUM(CASE 
                        WHEN tb_payment_detail.gaji_pokok < {$ump} THEN {$bpjsUmp}
                        ELSE ROUND(tb_payment_detail.gaji_pokok * 0.04, 0) 
                    END) as total_bpjs_4_persen"),
                DB::raw('SUM(tb_payment_detail.total_amoun) as total_gaji_bersih'),
                DB::raw("SUM(CASE WHEN tb_payment_detail.gaji_pokok < {$ump} THEN 1 ELSE 0 END) as pegawai_bawah_ump")
            )
                ->groupBy('pegawai_pw.skpd')
                ->orderBy('pegawai_pw.skpd')
                ->get();

            // Calculate grand total for heading/footer
            $grandTotal = [
                'jumlah_pegawai' => $summary->sum('jumlah_pegawai'),
                'total_gaji_pokok' => $summary->sum('total_gaji_pokok'),
                'total_bpjs_4_persen' => $summary->sum('total_bpjs_4_persen'),
                'total_gaji_bersih' => $summary->sum('total_gaji_bersih'),
                'pegawai_bawah_ump' => $summary->sum('pegawai_bawah_ump'),
            ];

            $fileName = "Rekon_BPJS_4persen_PerSKPD_{$month}_{$year}.xlsx";
            return Excel::download(new BpjsRekonSkpdExport($summary->toArray(), $month, $year, $grandTotal), $fileName);

        } elseif ($type === 'skpd-rekening') {
            $mappings = DB::table('pppk_pw_jabatan_mappings')->orderBy('order_weight', 'desc')->get();
            $skpdRekeningSummary = [];

            if ($mappings->isNotEmpty()) {
                $caseSql = "CASE ";
                foreach ($mappings as $m) {
                    $caseSql .= "WHEN pw.jabatan LIKE '%" . addslashes($m->keyword) . "%' THEN " . $m->id . " ";
                }
                $caseSql .= "ELSE 0 END as mapping_id";

                $skpdRekeningSummary = DB::select("
                    SELECT 
                        mapped.skpd,
                        mapped.mapping_id,
                        COALESCE(m.nama_kelompok, 'Lainnya') as nama_kelompok,
                        COALESCE(m.kode_rekening, '-') as kode_rekening,
                        COUNT(*) as jumlah_pegawai,
                        SUM(mapped.gaji_pokok) as total_gaji_pokok,
                        SUM(mapped.bpjs_4_persen) as total_bpjs_4_persen,
                        SUM(mapped.total_amoun) as total_gaji_bersih,
                        SUM(CASE WHEN mapped.gaji_pokok < ? THEN 1 ELSE 0 END) as pegawai_bawah_ump
                    FROM (
                        SELECT 
                            pw.skpd, pw.jabatan, pd.gaji_pokok, pd.total_amoun,
                            CASE 
                                WHEN pd.gaji_pokok < ? THEN ?
                                ELSE ROUND(pd.gaji_pokok * 0.04, 0) 
                            END as bpjs_4_persen,
                            $caseSql
                        FROM tb_payment_detail pd
                        JOIN pegawai_pw pw ON pd.employee_id = pw.id
                        JOIN tb_payment p  ON pd.payment_id = p.id
                        WHERE p.month = ? AND p.year = ? " . ($sumberDana && $sumberDana !== 'Semua' ? "AND pw.sumber_dana = " . DB::getPdo()->quote($sumberDana) : "") . "
                    ) as mapped
                    LEFT JOIN pppk_pw_jabatan_mappings m ON mapped.mapping_id = m.id
                    GROUP BY mapped.skpd, mapped.mapping_id, m.nama_kelompok, m.kode_rekening, m.order_weight
                    ORDER BY mapped.skpd ASC, m.order_weight DESC
                ", [$ump, $ump, $bpjsUmp, $month, $year]);
            }

            $grandTotal = [
                'jumlah_pegawai' => collect($skpdRekeningSummary)->sum('jumlah_pegawai'),
                'total_gaji_pokok' => collect($skpdRekeningSummary)->sum('total_gaji_pokok'),
                'total_bpjs_4_persen' => collect($skpdRekeningSummary)->sum('total_bpjs_4_persen'),
                'total_gaji_bersih' => collect($skpdRekeningSummary)->sum('total_gaji_bersih'),
                'pegawai_bawah_ump' => collect($skpdRekeningSummary)->sum('pegawai_bawah_ump'),
            ];

            $fileName = "Rekon_BPJS_4persen_PerSKPD_Rekening_{$month}_{$year}.xlsx";
            return Excel::download(new BpjsRekonSkpdRekeningExport($skpdRekeningSummary, $month, $year, $grandTotal), $fileName);

        } elseif ($type === 'rekening') {
            $mappings = DB::table('pppk_pw_jabatan_mappings')->orderBy('order_weight', 'desc')->get();
            $rekeningSummary = [];

            if ($mappings->isNotEmpty()) {
                $caseSql = "CASE ";
                foreach ($mappings as $m) {
                    $caseSql .= "WHEN pw.jabatan LIKE '%" . addslashes($m->keyword) . "%' THEN " . $m->id . " ";
                }
                $caseSql .= "ELSE 0 END as mapping_id";

                $rekeningSummary = DB::select("
                    SELECT 
                        mapped.mapping_id,
                        COALESCE(m.nama_kelompok, 'Lainnya') as nama_kelompok,
                        COALESCE(m.kode_rekening, '-') as kode_rekening,
                        COUNT(*) as jumlah_pegawai,
                        SUM(mapped.gaji_pokok) as total_gaji_pokok,
                        SUM(mapped.bpjs_4_persen) as total_bpjs_4_persen,
                        SUM(mapped.total_amoun) as total_gaji_bersih,
                        SUM(CASE WHEN mapped.gaji_pokok < ? THEN 1 ELSE 0 END) as pegawai_bawah_ump
                    FROM (
                        SELECT 
                            pw.jabatan, pd.gaji_pokok, pd.total_amoun,
                            CASE 
                                WHEN pd.gaji_pokok < ? THEN ?
                                ELSE ROUND(pd.gaji_pokok * 0.04, 0) 
                            END as bpjs_4_persen,
                            $caseSql
                        FROM tb_payment_detail pd
                        JOIN pegawai_pw pw ON pd.employee_id = pw.id
                        JOIN tb_payment p  ON pd.payment_id = p.id
                        WHERE p.month = ? AND p.year = ? " . ($sumberDana && $sumberDana !== 'Semua' ? "AND pw.sumber_dana = " . DB::getPdo()->quote($sumberDana) : "") . "
                    ) as mapped
                    LEFT JOIN pppk_pw_jabatan_mappings m ON mapped.mapping_id = m.id
                    GROUP BY mapped.mapping_id, m.nama_kelompok, m.kode_rekening, m.order_weight
                    ORDER BY m.order_weight DESC, m.nama_kelompok ASC
                ", [$ump, $ump, $bpjsUmp, $month, $year]);
            }

            $grandTotal = [
                'jumlah_pegawai' => collect($rekeningSummary)->sum('jumlah_pegawai'),
                'total_gaji_pokok' => collect($rekeningSummary)->sum('total_gaji_pokok'),
                'total_bpjs_4_persen' => collect($rekeningSummary)->sum('total_bpjs_4_persen'),
                'total_gaji_bersih' => collect($rekeningSummary)->sum('total_gaji_bersih'),
                'pegawai_bawah_ump' => collect($rekeningSummary)->sum('pegawai_bawah_ump'),
            ];

            $fileName = "Rekon_BPJS_4persen_PerRekening_{$month}_{$year}.xlsx";
            return Excel::download(new BpjsRekonRekeningExport($rekeningSummary, $month, $year, $grandTotal), $fileName);

        } elseif ($type === 'jabatan') {
            $summary = $query->select(
                'pegawai_pw.jabatan',
                DB::raw('COUNT(*) as jumlah_pegawai'),
                DB::raw('SUM(tb_payment_detail.gaji_pokok) as total_gaji_pokok'),
                DB::raw("SUM(CASE 
                        WHEN tb_payment_detail.gaji_pokok < {$ump} THEN {$bpjsUmp}
                        ELSE ROUND(tb_payment_detail.gaji_pokok * 0.04, 0) 
                    END) as total_bpjs_4_persen"),
                DB::raw('SUM(tb_payment_detail.total_amoun) as total_gaji_bersih'),
                DB::raw("SUM(CASE WHEN tb_payment_detail.gaji_pokok < {$ump} THEN 1 ELSE 0 END) as pegawai_bawah_ump")
            )
                ->groupBy('pegawai_pw.jabatan')
                ->orderBy('pegawai_pw.jabatan')
                ->get();

            // Calculate grand total for heading/footer
            $grandTotal = [
                'jumlah_pegawai' => $summary->sum('jumlah_pegawai'),
                'total_gaji_pokok' => $summary->sum('total_gaji_pokok'),
                'total_bpjs_4_persen' => $summary->sum('total_bpjs_4_persen'),
                'total_gaji_bersih' => $summary->sum('total_gaji_bersih'),
                'pegawai_bawah_ump' => $summary->sum('pegawai_bawah_ump'),
            ];

            $fileName = "Rekon_BPJS_4persen_PerJabatan_{$month}_{$year}.xlsx";
            return Excel::download(new BpjsRekonJabatanExport($summary->toArray(), $month, $year, $grandTotal), $fileName);

        } else {
            $detail = $query->select(
                'pegawai_pw.nip',
                'pegawai_pw.nama',
                'pegawai_pw.skpd',
                'pegawai_pw.upt',
                'pegawai_pw.jabatan',
                'tb_payment_detail.gaji_pokok',
                'tb_payment_detail.total_amoun',
                DB::raw("CASE 
                        WHEN tb_payment_detail.gaji_pokok < {$ump} THEN {$bpjsUmp}
                        ELSE ROUND(tb_payment_detail.gaji_pokok * 0.04, 0) 
                    END as bpjs_4_persen"),
                DB::raw("CASE 
                        WHEN tb_payment_detail.gaji_pokok < {$ump} THEN 'UMP'
                        ELSE 'GAJI' 
                    END as basis_hitung")
            )
                ->orderBy('pegawai_pw.skpd')
                ->orderBy('pegawai_pw.nama')
                ->get();

            $fileName = "Rekon_BPJS_4persen_Detail_{$month}_{$year}.xlsx";
            return Excel::download(new BpjsRekonDetailExport($detail->toArray(), $month, $year), $fileName);
        }
    }

    /**
     * Get current UMP setting.
     */
    public function getUmp()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'ump' => (float) Setting::getValue('ump_kalsel', 3725000),
            ],
        ]);
    }

    /**
     * Update UMP setting.
     */
    public function updateUmp(Request $request)
    {
        $request->validate([
            'ump' => 'required|numeric|min:1000000',
        ]);

        Setting::setValue('ump_kalsel', $request->ump, 'UMP Provinsi Kalimantan Selatan untuk dasar perhitungan BPJS 4%');

        return response()->json([
            'success' => true,
            'message' => 'UMP berhasil diperbarui menjadi Rp ' . number_format($request->ump, 0, ',', '.'),
            'data' => [
                'ump' => (float) $request->ump,
                'bpjs_4_persen' => round($request->ump * 0.04, 0),
            ],
        ]);
    }
}
