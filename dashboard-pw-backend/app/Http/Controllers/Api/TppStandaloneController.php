<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StandaloneTpp;
use App\Models\Skpd;

class TppStandaloneController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->query('month');
        $year = $request->query('year');
        $type = $request->query('type'); // pns, pppk

        $query = StandaloneTpp::with('skpd')
            ->where('month', $month)
            ->where('year', $year);

        if ($type) {
            $query->where('employee_type', $type);
        }

        $data = $query->orderBy('nama', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'skpd_id' => 'required|exists:skpd,id_skpd'
        ]);

        $item = StandaloneTpp::findOrFail($id);
        $item->update([
            'skpd_id' => $request->skpd_id
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'SKPD berhasil dihubungkan',
            'data' => $item->load('skpd')
        ]);
    }

    public function destroy($id)
    {
        $item = StandaloneTpp::findOrFail($id);
        $item->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data TPP standalone berhasil dihapus'
        ]);
    }
}
