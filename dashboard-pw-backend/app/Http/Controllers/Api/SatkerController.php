<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Satker;
use Illuminate\Http\Request;

class SatkerController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $satkers = Satker::paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $satkers->items(),
            'total' => $satkers->total(),
        ]);
    }
}
