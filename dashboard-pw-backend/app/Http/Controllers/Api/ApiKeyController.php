<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    /**
     * List semua API keys
     */
    public function index()
    {
        $keys = ApiKey::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => true,
            'data' => $keys,
        ]);
    }

    /**
     * Generate API key baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $plainKey = Str::random(64);

        $apiKey = ApiKey::create([
            'name' => $request->name,
            'key' => $plainKey,
            'is_active' => true,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'API Key berhasil dibuat. Simpan key ini, tidak akan ditampilkan lagi secara lengkap.',
            'data' => $apiKey,
            'plain_key' => $plainKey,
        ], 201);
    }

    /**
     * Toggle aktif/nonaktif API key
     */
    public function toggleActive($id)
    {
        $apiKey = ApiKey::findOrFail($id);
        $apiKey->update(['is_active' => !$apiKey->is_active]);

        return response()->json([
            'status' => true,
            'message' => $apiKey->is_active ? 'API Key diaktifkan.' : 'API Key dinonaktifkan.',
            'data' => $apiKey,
        ]);
    }

    /**
     * Hapus API key
     */
    public function destroy($id)
    {
        $apiKey = ApiKey::findOrFail($id);
        $apiKey->delete();

        return response()->json([
            'status' => true,
            'message' => 'API Key berhasil dihapus.',
        ]);
    }
}
