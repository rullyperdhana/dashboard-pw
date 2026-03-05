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
        $page = $request->input('page', 1);

        $satkers = \Illuminate\Support\Facades\Cache::remember("ref_satkers_limit_{$limit}_page_{$page}", 3600, function () use ($limit) {
            return \App\Models\Satker::paginate($limit);
        });

        return response()->json([
            'success' => true,
            'data' => $satkers->items(),
            'total' => $satkers->total(),
        ]);
    }
}
