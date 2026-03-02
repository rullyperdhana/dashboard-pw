<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GajiPns;
use App\Models\PayrollPosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GajiPnsController extends Controller
{
    public function index(Request $request)
    {
        $query = GajiPns::query();

        // Filter by period
        if ($request->has('bulan') && $request->has('tahun')) {
            $query->where('bulan', $request->bulan)->where('tahun', $request->tahun);
        } elseif ($request->has('tahun')) {
            $query->where('tahun', $request->tahun);
        }

        // Filter by SKPD
        if ($request->has('kdskpd')) {
            $query->where('kdskpd', $request->kdskpd);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('skpd', 'like', "%{$search}%");
            });
        }

        // Stats
        $statsQuery = clone $query;
        $stats = [
            'total' => $statsQuery->count(),
            'total_gaji_pokok' => (clone $statsQuery)->sum('gaji_pokok'),
            'total_kotor' => (clone $statsQuery)->sum('kotor'),
            'total_bersih' => (clone $statsQuery)->sum('bersih'),
        ];

        // Available periods
        $periods = GajiPns::select('bulan', 'tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->get();

        // Available SKPDs for current filter
        $skpdQuery = GajiPns::select('kdskpd', 'skpd')->distinct()->orderBy('skpd');
        if ($request->has('bulan') && $request->has('tahun')) {
            $skpdQuery->where('bulan', $request->bulan)->where('tahun', $request->tahun);
        }
        $skpds = $skpdQuery->get();

        $data = $query->orderBy('skpd')->orderBy('nama')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $data,
            'stats' => $stats,
            'periods' => $periods,
            'skpds' => $skpds,
        ]);
    }

    public function show($id)
    {
        $record = GajiPns::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $record,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nip' => 'required|string',
            'nama' => 'required|string',
            'golongan' => 'nullable|string',
            'kdpangkat' => 'nullable|string',
            'jabatan' => 'nullable|string',
            'skpd' => 'nullable|string',
            'satker' => 'nullable|string',
            'kdskpd' => 'nullable|string',
            'kdjenkel' => 'nullable|string',
            'pendidikan' => 'nullable|string',
            'norek' => 'nullable|string',
            'npwp' => 'nullable|string',
            'noktp' => 'nullable|string',
            'gaji_pokok' => 'nullable|numeric',
            'tunj_istri' => 'nullable|numeric',
            'tunj_anak' => 'nullable|numeric',
            'tunj_fungsional' => 'nullable|numeric',
            'tunj_struktural' => 'nullable|numeric',
            'tunj_umum' => 'nullable|numeric',
            'tunj_beras' => 'nullable|numeric',
            'tunj_pph' => 'nullable|numeric',
            'tunj_tpp' => 'nullable|numeric',
            'tunj_eselon' => 'nullable|numeric',
            'tunj_guru' => 'nullable|numeric',
            'tunj_langka' => 'nullable|numeric',
            'tunj_tkd' => 'nullable|numeric',
            'tunj_terpencil' => 'nullable|numeric',
            'tunj_khusus' => 'nullable|numeric',
            'tunj_askes' => 'nullable|numeric',
            'tunj_kk' => 'nullable|numeric',
            'tunj_km' => 'nullable|numeric',
            'pembulatan' => 'nullable|numeric',
            'kotor' => 'nullable|numeric',
            'pot_iwp' => 'nullable|numeric',
            'pot_iwp1' => 'nullable|numeric',
            'pot_iwp8' => 'nullable|numeric',
            'pot_askes' => 'nullable|numeric',
            'pot_pph' => 'nullable|numeric',
            'pot_bulog' => 'nullable|numeric',
            'pot_taperum' => 'nullable|numeric',
            'pot_sewa' => 'nullable|numeric',
            'pot_hutang' => 'nullable|numeric',
            'pot_korpri' => 'nullable|numeric',
            'pot_irdhata' => 'nullable|numeric',
            'pot_koperasi' => 'nullable|numeric',
            'pot_jkk' => 'nullable|numeric',
            'pot_jkm' => 'nullable|numeric',
            'total_potongan' => 'nullable|numeric',
            'bersih' => 'nullable|numeric',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2000',
            'jenis_gaji' => 'nullable|string',
        ]);

        // Check if posted
        if (PayrollPosting::isLocked($validated['tahun'], $validated['bulan'], 'PNS')) {
            return response()->json([
                'success' => false,
                'message' => "Periode {$validated['bulan']}/{$validated['tahun']} sudah di-POSTING (Dikunci) dan tidak dapat diubah."
            ], 403);
        }

        $record = GajiPns::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data gaji PNS berhasil ditambahkan',
            'data' => $record,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $record = GajiPns::findOrFail($id);

        $validated = $request->validate([
            'nip' => 'sometimes|string',
            'nama' => 'sometimes|string',
            'golongan' => 'nullable|string',
            'kdpangkat' => 'nullable|string',
            'jabatan' => 'nullable|string',
            'skpd' => 'nullable|string',
            'satker' => 'nullable|string',
            'kdskpd' => 'nullable|string',
            'kdjenkel' => 'nullable|string',
            'pendidikan' => 'nullable|string',
            'norek' => 'nullable|string',
            'npwp' => 'nullable|string',
            'noktp' => 'nullable|string',
            'gaji_pokok' => 'nullable|numeric',
            'tunj_istri' => 'nullable|numeric',
            'tunj_anak' => 'nullable|numeric',
            'tunj_fungsional' => 'nullable|numeric',
            'tunj_struktural' => 'nullable|numeric',
            'tunj_umum' => 'nullable|numeric',
            'tunj_beras' => 'nullable|numeric',
            'tunj_pph' => 'nullable|numeric',
            'tunj_tpp' => 'nullable|numeric',
            'tunj_eselon' => 'nullable|numeric',
            'tunj_guru' => 'nullable|numeric',
            'tunj_langka' => 'nullable|numeric',
            'tunj_tkd' => 'nullable|numeric',
            'tunj_terpencil' => 'nullable|numeric',
            'tunj_khusus' => 'nullable|numeric',
            'tunj_askes' => 'nullable|numeric',
            'tunj_kk' => 'nullable|numeric',
            'tunj_km' => 'nullable|numeric',
            'pembulatan' => 'nullable|numeric',
            'kotor' => 'nullable|numeric',
            'pot_iwp' => 'nullable|numeric',
            'pot_iwp1' => 'nullable|numeric',
            'pot_iwp8' => 'nullable|numeric',
            'pot_askes' => 'nullable|numeric',
            'pot_pph' => 'nullable|numeric',
            'pot_bulog' => 'nullable|numeric',
            'pot_taperum' => 'nullable|numeric',
            'pot_sewa' => 'nullable|numeric',
            'pot_hutang' => 'nullable|numeric',
            'pot_korpri' => 'nullable|numeric',
            'pot_irdhata' => 'nullable|numeric',
            'pot_koperasi' => 'nullable|numeric',
            'pot_jkk' => 'nullable|numeric',
            'pot_jkm' => 'nullable|numeric',
            'total_potongan' => 'nullable|numeric',
            'bersih' => 'nullable|numeric',
            'bulan' => 'sometimes|integer|min:1|max:12',
            'tahun' => 'sometimes|integer|min:2000',
            'jenis_gaji' => 'nullable|string',
        ]);

        // Check if posted (either current or new period)
        if (PayrollPosting::isLocked($record->tahun, $record->bulan, 'PNS')) {
            return response()->json([
                'success' => false,
                'message' => "Periode saat ini ({$record->bulan}/{$record->tahun}) sudah di-POSTING (Dikunci) dan tidak dapat diubah."
            ], 403);
        }

        if (isset($validated['bulan']) && isset($validated['tahun'])) {
            if (PayrollPosting::isLocked($validated['tahun'], $validated['bulan'], 'PNS')) {
                return response()->json([
                    'success' => false,
                    'message' => "Periode baru ({$validated['bulan']}/{$validated['tahun']}) sudah di-POSTING (Dikunci) dan tidak dapat digunakan."
                ], 403);
            }
        }

        $record->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data gaji PNS berhasil diperbarui',
            'data' => $record,
        ]);
    }

    public function destroy($id)
    {
        $record = GajiPns::findOrFail($id);

        // Check if posted
        if (PayrollPosting::isLocked($record->tahun, $record->bulan, 'PNS')) {
            return response()->json([
                'success' => false,
                'message' => "Periode {$record->bulan}/{$record->tahun} sudah di-POSTING (Dikunci) dan data tidak dapat dihapus."
            ], 403);
        }

        $record->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data gaji PNS berhasil dihapus',
        ]);
    }
}
