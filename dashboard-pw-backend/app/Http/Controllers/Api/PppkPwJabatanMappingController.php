<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PppkPwJabatanMapping;
use Illuminate\Http\Request;

class PppkPwJabatanMappingController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => PppkPwJabatanMapping::orderBy('order_weight', 'desc')->orderBy('nama_kelompok', 'asc')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kelompok' => 'required|string|max:255',
            'kode_rekening' => 'required|string|max:255',
            'keyword' => 'required|string|max:255',
            'order_weight' => 'nullable|integer',
        ]);

        $mapping = PppkPwJabatanMapping::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Mapping jabatan berhasil ditambahkan',
            'data' => $mapping
        ]);
    }

    public function update(Request $request, $id)
    {
        $mapping = PppkPwJabatanMapping::findOrFail($id);

        $validated = $request->validate([
            'nama_kelompok' => 'required|string|max:255',
            'kode_rekening' => 'required|string|max:255',
            'keyword' => 'required|string|max:255',
            'order_weight' => 'nullable|integer',
        ]);

        $mapping->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Mapping jabatan berhasil diperbarui',
            'data' => $mapping
        ]);
    }

    public function destroy($id)
    {
        $mapping = PppkPwJabatanMapping::findOrFail($id);
        $mapping->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mapping jabatan berhasil dihapus'
        ]);
    }
}
