<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SkpdController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\PnsPayrollController;
use App\Http\Controllers\Api\SkpdMappingController;
use App\Http\Controllers\Api\TppController;
use App\Http\Controllers\Api\TpgController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\GajiPnsController;
use App\Http\Controllers\Api\GajiPppkController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UploadJobController;
use App\Http\Controllers\Api\BpjsRekonController;
use App\Http\Controllers\Api\SumberDanaSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/captcha', [AuthController::class, 'getCaptcha']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/settings/pppk-pw-estimation', [SettingController::class, 'pppkPwEstimation']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // SKPD
    Route::get('/skpd', [SkpdController::class, 'index']);

    // SKPD Mapping
    Route::get('/skpd-mapping', [SkpdMappingController::class, 'index']);
    Route::get('/skpd-mapping/unmapped', [SkpdMappingController::class, 'unmapped']);
    Route::post('/skpd-mapping', [SkpdMappingController::class, 'store']);
    Route::post('/skpd-mapping/bulk', [SkpdMappingController::class, 'bulkStore']);
    Route::delete('/skpd-mapping/{id}', [SkpdMappingController::class, 'destroy']);

    // Reports
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/unpaid-skpds', [ReportController::class, 'unpaidSkpds']);
    Route::get('/reports/unpaid-upts', [ReportController::class, 'unpaidUpts']);
    Route::get('/reports/unpaid-employees', [ReportController::class, 'unpaidEmployees']);
    Route::get('/reports/unpaid-export', [ReportController::class, 'exportUnpaid']);
    Route::get('/reports/paid-skpds', [ReportController::class, 'paidSkpds']);
    Route::get('/reports/paid-export', [ReportController::class, 'exportPaidSkpds']);
    Route::get('/reports/paid-employees', [ReportController::class, 'paidEmployees']);
    Route::get('/reports/paid-employees-export', [ReportController::class, 'exportPaidEmployees']);

    // PNS Payroll
    Route::post('/pns/upload', [PnsPayrollController::class, 'upload']);
    Route::get('/pns/dashboard', [PnsPayrollController::class, 'dashboard']);
    Route::get('/pns/list', [PnsPayrollController::class, 'list']);
    Route::get('/pns/trend', [PnsPayrollController::class, 'yearlyTrend']);
    Route::get('/pns/annual-report', [PnsPayrollController::class, 'annualReport']);

    // PPPK Payroll
    Route::post('/pppk/upload', [PnsPayrollController::class, 'uploadPppk']);
    Route::get('/pppk/dashboard', [PnsPayrollController::class, 'dashboardPppk']);
    Route::get('/pppk/trend', [PnsPayrollController::class, 'yearlyTrendPppk']);

    Route::get('/pppk/trend', [PnsPayrollController::class, 'yearlyTrendPppk']);

    // TPP Upload
    Route::post('/tpp/upload', [TppController::class, 'upload']);
    Route::get('/tpp/template', [TppController::class, 'downloadTemplate']);

    // TPG (Tunjangan Profesi Guru)
    Route::post('/tpg/upload', [TpgController::class, 'upload']);
    Route::get('/tpg/dashboard', [TpgController::class, 'dashboard']);
    Route::get('/tpg/data', [TpgController::class, 'data']);
    Route::get('/tpg/export', [TpgController::class, 'export']);

    // Employees
    Route::get('/employees/statuses', [EmployeeController::class, 'getStatuses']);
    Route::get('/employees/export', [EmployeeController::class, 'export']);
    Route::get('/employees/{id}/history', [EmployeeController::class, 'payrollHistory']);
    Route::get('/employees/{id}/history-export', [EmployeeController::class, 'exportIndividualPayroll']);
    Route::post('/employees/{id}/status', [EmployeeController::class, 'updateStatus']);
    Route::get('/employees/{id}/documents', [EmployeeController::class, 'getDocuments']);
    Route::post('/employees/{id}/documents', [EmployeeController::class, 'uploadDocument']);
    Route::delete('/employees/{id}/documents/{documentId}', [EmployeeController::class, 'deleteDocument']);
    Route::get('/employees/{id}/documents/{documentId}/download', [EmployeeController::class, 'downloadDocument']);
    Route::apiResource('employees', EmployeeController::class);

    // Gaji PNS & PPPK CRUD
    Route::apiResource('gaji-pns', GajiPnsController::class);
    Route::apiResource('gaji-pppk', GajiPppkController::class);

    // Settings Routes
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'update']);
    Route::get('/settings/pppk-estimation', [SettingController::class, 'pppkEstimation']);
    Route::get('/settings/pns-estimation', [SettingController::class, 'pnsEstimation']);

    // Payments
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::get('/{id}', [PaymentController::class, 'show']);
        Route::get('/{id}/pdf', [PaymentController::class, 'downloadPdf']);
        Route::post('/{id}/approve', [PaymentController::class, 'approve']);
    });

    // User Management
    Route::apiResource('users', UserController::class);
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);

    // Upload Jobs (Queue-based)
    Route::post('/upload-jobs', [UploadJobController::class, 'store']);
    Route::get('/upload-jobs', [UploadJobController::class, 'index']);
    Route::get('/upload-jobs/{id}', [UploadJobController::class, 'show']);

    // BPJS Rekon
    Route::get('/bpjs-rekon', [BpjsRekonController::class, 'index']);
    Route::get('/bpjs-rekon/ump', [BpjsRekonController::class, 'getUmp']);
    Route::put('/bpjs-rekon/ump', [BpjsRekonController::class, 'updateUmp']);
    Route::get('/bpjs-rekon/export', [BpjsRekonController::class, 'export']);

    // Sumber Dana Settings
    Route::get('/sumber-dana', [SumberDanaSettingController::class, 'index']);
    Route::put('/sumber-dana', [SumberDanaSettingController::class, 'update']);
    Route::put('/sumber-dana/bulk', [SumberDanaSettingController::class, 'bulkUpdate']);
});
