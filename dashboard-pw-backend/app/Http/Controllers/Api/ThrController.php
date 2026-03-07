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

        // n = months from Jan 2026 to thrMonth, max 2 months
        $nMonths = min((int) $thrMonth, 2);

        $user = auth()->user();

        // Query tb_payment and tb_payment_detail for month 2, joined with pegawai_pw and rka_settings
        $query = DB::table('tb_payment_detail as pd')
            ->join('tb_payment as p', 'pd.payment_id', '=', 'p.id')
            ->join('rka_settings as rs', 'p.rka_id', '=', 'rs.id')
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
            DB::raw('COALESCE(s.nama_skpd, e.skpd) as skpd_name'),
            'rs.kode_sub_giat',
            'rs.nama_sub_giat'
        )
            ->orderBy('skpd_name')
            ->orderBy('rs.kode_sub_giat')
            ->orderBy('e.nama')
            ->get();

        $grouped = $employees->groupBy('skpd_name')->map(function ($skpdItems, $skpdName) use ($nMonths) {
            $subGiatGroups = $skpdItems->groupBy(function ($item) {
                return '[' . $item->kode_sub_giat . '] ' . $item->nama_sub_giat;
            })->map(function ($subGiatItems, $subGiatName) use ($nMonths) {
                $mappedEmployees = $subGiatItems->map(function ($emp) use ($nMonths) {
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
                    'sub_giat_name' => $subGiatName,
                    'employees' => $mappedEmployees,
                    'subtotal_thr' => $mappedEmployees->sum('thr_amount'),
                    'employee_count' => $mappedEmployees->count()
                ];
            })->values();

            return [
                'skpd_name' => $skpdName,
                'sub_giat_groups' => $subGiatGroups,
                'total_employees_skpd' => $skpdItems->count(),
                'total_thr_skpd' => $subGiatGroups->sum('subtotal_thr')
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
                'total_thr_amount' => $grouped->sum('total_thr_skpd')
            ]
        ]);
    }

    public function verifyThr(Request $request)
    {
        return view('verify_thr', [
            'total' => $request->total,
            'period' => $request->period,
            'date' => $request->date
        ]);
    }

    public function exportExcel(Request $request)
    {
        $year = $request->year ?? 2026;
        $thrMonth = $request->month ?? 4;
        $nMonths = min((int) $thrMonth, 2);

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
        $nMonths = min((int) $thrMonth, 2);

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

        $printDate = now()->format('d/m/Y H:i');
        $totalFormatted = number_format($response->getData()->meta->total_thr_amount, 0, ',', '.');

        // Generate Verification URL - Using /api prefix for more reliable routing on VPS
        $verifyUrl = "https://simgajitaspen.my.id/api/verify-thr?" . http_build_query([
            'total' => $totalFormatted,
            'period' => $thrMonthName . ' ' . $year,
            'date' => $printDate
        ]);

        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($verifyUrl);

        // Fetch Signature Data from report_settings linked to user's SKPD
        $user = auth()->user();
        $querySettings = DB::table('report_settings');
        if ($user && !empty($user->institution)) {
            $querySettings->where('skpdid', $user->institution);
        }
        $reportSettings = $querySettings->first() ?: DB::table('report_settings')->first();

        // Ensure reportSettings is never null to avoid 500 error in view
        if (!$reportSettings) {
            $reportSettings = (object) [
                'nama_kepala' => null,
                'nip_kepala' => null,
                'jabatan_kepala' => null
            ];
        }

        // Fetch QR code and convert to base64 for dompdf compatibility
        try {
            $qrCodeBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($qrUrl));
        } catch (\Exception $e) {
            $qrCodeBase64 = null;
        }

        $pdf = Pdf::loadView('reports.thr_pppk_pw', [
            'data' => $dataArray,
            'year' => $year,
            'nMonths' => $nMonths,
            'thrMonthName' => $thrMonthName,
            'totalAmount' => $response->getData()->meta->total_thr_amount,
            'printDate' => $printDate,
            'qrCode' => $qrCodeBase64,
            'reportSettings' => $reportSettings
        ])->setPaper('a4', 'landscape')->setOption('isPhpEnabled', true);

        // Standard DOMPDF way to add Page X on every page
        $canvas = $pdf->getCanvas();
        $font = $pdf->getDomPDF()->getFontMetrics()->get_font("sans-serif", "normal");
        $canvas->page_text(380, 565, "Halaman {PAGE_NUM}", $font, 10, array(0.46, 0.46, 0.46));

        return $pdf->download("THR_PPPK_PW_{$year}_{$thrMonth}.pdf");
    }
}
