<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skpd;
use Illuminate\Http\Request;

class SkpdController extends Controller
{
    public function index()
    {
        $skpds = \Illuminate\Support\Facades\Cache::remember('ref_skpds', 3600, function () {
            return \App\Models\Skpd::orderBy('nama_skpd')->get();
        });

        return response()->json([
            'success' => true,
            'data' => $skpds
        ]);
    }
}
