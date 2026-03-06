<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// DEBUG: Direct string return to bypass controller/view issues
Route::get('/verify-thr', function (\Illuminate\Http\Request $request) {
    return "VERIFIKASI BERHASIL: Total Rp " . ($request->total ?? '0') . " | Periode: " . ($request->period ?? '-');
});

