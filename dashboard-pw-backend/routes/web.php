<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/verify-thr', [\App\Http\Controllers\Api\ThrController::class, 'verifyThr'])->name('verify.thr');

