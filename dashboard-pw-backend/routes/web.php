<?php

use App\Http\Controllers\Api\EssAuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/verify/slip', [EssAuthController::class, 'verifySlip']);

