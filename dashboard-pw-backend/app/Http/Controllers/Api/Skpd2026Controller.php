<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skpd2026;
use Illuminate\Http\Request;

class Skpd2026Controller extends Controller
{
    /**
     * Get all SKPD 2026 records
     */
    public function index()
    {
        $skpds = Skpd2026::orderBy('kode_skpd')->get();
        return response()->json([
            'success' => true,
            'data' => $skpds
        ]);
    }
}
