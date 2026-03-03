<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterKeluarga;
use Illuminate\Http\Request;

class MasterKeluargaController extends Controller
{
    public function index(Request $request)
    {
        $query = MasterKeluarga::query();

        if ($request->has('nip')) {
            $query->where('nip', $request->nip);
        }

        if ($request->has('nmkel')) {
            $query->where('nmkel', 'like', "%{$request->nmkel}%");
        }

        $data = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
