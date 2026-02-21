<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skpd;
use Illuminate\Http\Request;

class SkpdController extends Controller
{
    public function index()
    {
        $skpds = Skpd::orderBy('nama_skpd')->get();
        return response()->json([
            'success' => true,
            'data' => $skpds
        ]);
    }
}
