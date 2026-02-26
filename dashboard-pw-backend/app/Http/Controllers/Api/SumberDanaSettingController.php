<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SumberDanaSettingController extends Controller
{
    /**
     * List all distinct SKPDs with their sumber_dana flag.
     * Shows the dominant sumber_dana per SKPD and employee count.
     */
    public function index()
    {
        $skpdList = DB::table('pegawai_pw')
            ->select(
                'skpd',
                DB::raw('COUNT(*) as jumlah_pegawai'),
                DB::raw("MAX(sumber_dana) as sumber_dana")
            )
            ->whereNotNull('skpd')
            ->where('skpd', '!=', '')
            ->groupBy('skpd')
            ->orderBy('skpd')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $skpdList,
        ]);
    }

    /**
     * Bulk update sumber_dana for all employees under a specific SKPD.
     */
    public function update(Request $request)
    {
        $request->validate([
            'skpd' => 'required|string',
            'sumber_dana' => 'required|in:APBD,BLUD',
        ]);

        $affected = Employee::where('skpd', $request->skpd)
            ->update(['sumber_dana' => $request->sumber_dana]);

        return response()->json([
            'success' => true,
            'message' => "Berhasil update {$affected} pegawai di {$request->skpd} ke {$request->sumber_dana}.",
            'affected' => $affected,
        ]);
    }

    /**
     * Bulk update multiple SKPDs at once.
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'updates' => 'required|array',
            'updates.*.skpd' => 'required|string',
            'updates.*.sumber_dana' => 'required|in:APBD,BLUD',
        ]);

        $totalAffected = 0;
        foreach ($request->updates as $item) {
            $affected = Employee::where('skpd', $item['skpd'])
                ->update(['sumber_dana' => $item['sumber_dana']]);
            $totalAffected += $affected;
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil update {$totalAffected} pegawai.",
            'affected' => $totalAffected,
        ]);
    }
}
