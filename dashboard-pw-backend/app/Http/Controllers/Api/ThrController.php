<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PegawaiPw;
use App\Exports\ThrExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ThrController extends Controller
{
    /**
     * Calculate THR for PPPK Paruh Waktu
     * Formula: gapok * (n/12) where n is months worked since Jan 1, 2026
     */
    public function pppkPwThr(Request $request)
    {
        $year = $request->year ?? 2026;
        $thrMonth = $request->month ?? 4; // Default to April (4)

        // n = months from Jan 2026 to thrMonth
        $nMonths = $thrMonth;

        $user = auth()->user();

        // Query tb_payment and tb_payment_detail for month 2, joined with pegawai_pw
        $query = DB::table('tb_payment_detail as pd')
            ->join('tb_payment as p', 'pd.payment_id', '=', 'p.id')
            ->join('pegawai_pw as e', 'pd.employee_id', '=', 'e.id')
            ->leftJoin('skpd as s', 'e.idskpd', '=', 's.id_skpd')
            ->where('p.month', 2)
            ->where('p.year', $year);

        // Filter by SKPD if user is operator
        if ($user && $user->role === 'operator' && !empty($user->institution)) {
            $query->where('e.idskpd', $user->institution);
        }

        $employees = $query->select(
            'e.nip',
            'e.nama',
            'e.jabatan',
            'pd.gaji_pokok as gapok_basis',
            DB::raw('COALESCE(s.nama_skpd, e.skpd) as skpd_name')
        )
            ->orderBy('skpd_name')
            ->orderBy('e.nama')
            ->get();

        $grouped = $employees->groupBy('skpd_name')->map(function ($items, $skpdName) use ($nMonths) {
            $mappedItems = $items->map(function ($emp) use ($nMonths) {
                // Ensure gapok is treated as float from payment detail
                $gapok = (float) $emp->gapok_basis;
                return [
                    'nip' => $emp->nip,
                    'nama' => $emp->nama,
                    'jabatan' => $emp->jabatan,
                    'gapok_basis' => $gapok,
                    'n_months' => $nMonths,
                    'thr_amount' => round($gapok * ($nMonths / 12))
                ];
            });

            return [
                'skpd_name' => $skpdName,
                'employees' => $mappedItems,
                'subtotal_thr' => $mappedItems->sum('thr_amount'),
                'employee_count' => $mappedItems->count()
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $grouped,
            'meta' => [
                'year' => (int) $year,
                'thr_month' => (int) $thrMonth,
                'n_months' => (int) $nMonths,
                'calculation_basis' => 'Gaji Pokok Pebruari',
                'total_employees' => $employees->count(),
                'total_thr_amount' => $grouped->sum('subtotal_thr')
            ]
        ]);
    }

    public function exportExcel(Request $request)
    {
        $year = $request->year ?? 2026;
        $thrMonth = $request->month ?? 4;
        $nMonths = $thrMonth;

        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];
        $thrMonthName = $monthNames[$thrMonth] ?? 'Unknown';

        $response = $this->pppkPwThr($request);
        $data = $response->getData()->data;
        $dataArray = json_decode(json_encode($data), true);

        return Excel::download(
            new ThrExport($dataArray, $year, $nMonths, $thrMonthName),
            "THR_PPPK_PW_{$year}_{$thrMonth}.xlsx"
        );
    }

    public function exportPdf(Request $request)
    {
        $year = $request->year ?? 2026;
        $thrMonth = $request->month ?? 4;
        $nMonths = $thrMonth;

        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];
        $thrMonthName = $monthNames[$thrMonth] ?? 'Unknown';

        $response = $this->pppkPwThr($request);
        $data = $response->getData()->data;
        $dataArray = json_decode(json_encode($data), true);

        $pdf = Pdf::loadView('reports.thr_pppk_pw', [
            'data' => $dataArray,
            'year' => $year,
            'nMonths' => $nMonths,
            'thrMonthName' => $thrMonthName,
            'totalAmount' => $response->getData()->meta->total_thr_amount,
            'printDate' => now()->format('d/m/Y H:i')
        ])->setPaper('a4', 'landscape');

        return $pdf->download("THR_PPPK_PW_{$year}_{$thrMonth}.pdf");
    }
}
