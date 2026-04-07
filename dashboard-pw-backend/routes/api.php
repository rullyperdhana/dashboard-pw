<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\BudgetPredictionController;
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
use App\Http\Controllers\Api\PayrollPostingController;
use App\Http\Controllers\Api\MasterPegawaiController;
use App\Http\Controllers\Api\DbfImportController;
use App\Http\Controllers\Api\SatkerController;
use App\Http\Controllers\Api\ThrController;
use App\Http\Controllers\Api\Gaji13Controller;
use App\Http\Controllers\Api\ExportLogController;
use App\Http\Controllers\Api\Skpd2026Controller;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\PPh21Controller;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\EssAuthController;
use App\Http\Controllers\Api\CacheController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/captcha', [AuthController::class, 'getCaptcha']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login');
Route::get('/settings/pppk-pw-estimation', [SettingController::class, 'pppkPwEstimation']);
Route::get('/verify-thr', [ThrController::class, 'verifyThr']);
Route::get('/verify-payment', [PaymentController::class, 'verifyPayment']);
Route::get('/verify/slip', [EssAuthController::class, 'verifySlip']);

// ESS Public routes
Route::get('/ess/captcha', [EssAuthController::class, 'getCaptcha']);
Route::post('/ess/login', [EssAuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/ess/slips', [EssAuthController::class, 'slips']);
Route::get('/ess/slips/{id}/detail', [EssAuthController::class, 'slipDetail']);
Route::get('/ess/slips/{id}/pdf', [EssAuthController::class, 'downloadPdf']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('throttle:30,1');
    Route::get('/dashboard/executive', [DashboardController::class, 'executiveSummary'])->middleware('throttle:30,1');
    Route::get('/budget-prediction', [BudgetPredictionController::class, 'index']);
    Route::apiResource('upload-jobs', UploadJobController::class);

    // SKPD
    Route::get('/skpd', [SkpdController::class, 'index']);

    // SKPD Mapping
    Route::get('/skpd-mapping', [SkpdMappingController::class, 'index']);
    Route::get('/skpd-mapping/unmapped', [SkpdMappingController::class, 'unmapped']);
    Route::get('/skpd-2026', [Skpd2026Controller::class, 'index']);
    Route::post('/skpd-mapping', [SkpdMappingController::class, 'store']);
    Route::post('/skpd-mapping/bulk', [SkpdMappingController::class, 'bulkStore']);
    Route::delete('/skpd-mapping', [SkpdMappingController::class, 'destroyAll']);
    Route::delete('/skpd-mapping/{id}', [SkpdMappingController::class, 'destroy']);

    // Analytics
    Route::get('/analytics/health', [AnalyticsController::class, 'health']);
    // Reports
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/export-logs', [ExportLogController::class, 'index']);
    Route::delete('/export-logs/cleanup', [ExportLogController::class, 'cleanup']);
    Route::get('/reports/unpaid-skpds', [ReportController::class, 'unpaidSkpds']);
    Route::get('/reports/unpaid-upts', [ReportController::class, 'unpaidUpts']);
    Route::get('/reports/unpaid-employees', [ReportController::class, 'unpaidEmployees']);
    Route::get('/reports/unpaid-export', [ReportController::class, 'exportUnpaid']);
    Route::get('/reports/paid-skpds', [ReportController::class, 'paidSkpds']);
    Route::get('/reports/paid-export', [ReportController::class, 'exportPaidSkpds']);
    Route::get('/reports/paid-employees', [ReportController::class, 'paidEmployees']);
    Route::get('/reports/paid-employees-export', [ReportController::class, 'exportPaidEmployees']);
    Route::get('/reports/combined-allowance-export', [ReportController::class, 'exportCombinedAllowance']);

    // THR (PPPK Paruh Waktu)
    Route::get('/thr/pppk-pw', [ThrController::class, 'index']);
    Route::get('/thr/pppk-pw/summary', [ThrController::class, 'summary']);
    Route::get('/thr/pppk-pw/missing', [ThrController::class, 'missing']);
    Route::get('/thr/pppk-pw/missing/export', [ThrController::class, 'exportMissing']);
    Route::post('/thr/pppk-pw/generate', [ThrController::class, 'generate']);
    Route::post('/thr/pppk-pw/store', [ThrController::class, 'storeRow']);
    Route::put('/thr/pppk-pw/{id}', [ThrController::class, 'updateRow']);
    Route::delete('/thr/pppk-pw/{id}', [ThrController::class, 'deleteRow']);
    Route::get('/thr/pppk-pw/excel', [ThrController::class, 'exportExcel']);
    Route::get('/thr/pppk-pw/summary/export', [ThrController::class, 'exportSummaryExcel']);
    Route::get('/thr/pppk-pw/pdf', [ThrController::class, 'exportPdf']);

    // Gaji 13 (PPPK Paruh Waktu)
    Route::get('/gaji13/pppk-pw', [Gaji13Controller::class, 'index']);
    Route::get('/gaji13/pppk-pw/summary', [Gaji13Controller::class, 'summary']);
    Route::get('/gaji13/pppk-pw/missing', [Gaji13Controller::class, 'missing']);
    Route::get('/gaji13/pppk-pw/missing/export', [Gaji13Controller::class, 'exportMissing']);
    Route::post('/gaji13/pppk-pw/generate', [Gaji13Controller::class, 'generate']);
    Route::post('/gaji13/pppk-pw/store', [Gaji13Controller::class, 'storeRow']);
    Route::put('/gaji13/pppk-pw/{id}', [Gaji13Controller::class, 'updateRow']);
    Route::delete('/gaji13/pppk-pw/{id}', [Gaji13Controller::class, 'deleteRow']);
    Route::get('/gaji13/pppk-pw/excel', [Gaji13Controller::class, 'exportExcel']);
    Route::get('/gaji13/pppk-pw/summary/export', [Gaji13Controller::class, 'exportSummaryExcel']);
    Route::get('/gaji13/pppk-pw/pdf', [Gaji13Controller::class, 'exportPdf']);

    // PNS Payroll
    Route::get('/pns/dashboard', [PnsPayrollController::class, 'dashboard']);
    Route::get('/pns/list', [PnsPayrollController::class, 'list']);
    Route::get('/pns/trend', [PnsPayrollController::class, 'yearlyTrend']);
    Route::get('/pns/annual-report', [PnsPayrollController::class, 'annualReport']);
    Route::get('/pns/export-annual-report', [PnsPayrollController::class, 'exportAnnualReport']);

    // PPPK Payroll
    Route::get('/pppk/dashboard', [PnsPayrollController::class, 'dashboardPppk']);
    Route::get('/pppk/trend', [PnsPayrollController::class, 'yearlyTrendPppk']);

    Route::prefix('pph21')->group(function () {
        Route::get('/report', [PPh21Controller::class, 'report']);
        Route::post('/calculate', [PPh21Controller::class, 'calculate']);
        Route::get('/export-a2', [PPh21Controller::class, 'exportA2']);
        Route::get('/monitoring', [PPh21Controller::class, 'monitoring']);
        Route::get('/export-monitoring', [PPh21Controller::class, 'exportMonitoring']);
        Route::delete('/', [PPh21Controller::class, 'destroy']);
    });

    // TPP Upload
    Route::post('/tpp/validate-upload', [TppController::class, 'validateUpload']);
    Route::post('/tpp/upload', [TppController::class, 'upload']);
    Route::get('/tpp/template', [TppController::class, 'downloadTemplate']);
    Route::get('/tpp/discrepancies', [TppController::class, 'getDiscrepancies']);
    
    // TPP Standalone (Unmapped)
    Route::get('/tpp/standalone', [App\Http\Controllers\Api\TppStandaloneController::class, 'index']);
    Route::put('/tpp/standalone/{id}', [App\Http\Controllers\Api\TppStandaloneController::class, 'update']);
    Route::delete('/tpp/standalone/{id}', [App\Http\Controllers\Api\TppStandaloneController::class, 'destroy']);

    // TPG (Tunjangan Profesi Guru)
    Route::post('/tpg/upload', [TpgController::class, 'upload']);
    Route::get('/tpg/dashboard', [TpgController::class, 'dashboard']);
    Route::get('/tpg/data', [TpgController::class, 'data']);
    Route::get('/tpg/export', [TpgController::class, 'export']);

    // Employees
    Route::get('/employees/get-stats', [EmployeeController::class, 'stats']);
    Route::get('/employees/statuses', [EmployeeController::class, 'getStatuses']);
    Route::get('/employees/export', [EmployeeController::class, 'export']);
    Route::get('/employees/{id}/history', [EmployeeController::class, 'payrollHistory']);
    Route::get('/employees/{id}/gpok-history', [EmployeeController::class, 'gpokHistory']);
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
    Route::get('/settings/pppk-estimation-detail', [SettingController::class, 'pppkEstimationDetail']);
    Route::get('/settings/pns-estimation-detail', [SettingController::class, 'pnsEstimationDetail']);
    Route::get('/settings/pppk-pw-estimation-detail', [SettingController::class, 'pppkPwEstimationDetail']);
    Route::get('/settings/pppk-estimation-export', [SettingController::class, 'pppkEstimationExport']);
    Route::get('/settings/pns-estimation-export', [SettingController::class, 'pnsEstimationExport']);
    Route::get('/settings/pppk-pw-estimation-export', [SettingController::class, 'pppkPwEstimationExport']);

    // Payments
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::get('/{id}', [PaymentController::class, 'show']);
        Route::get('/{id}/pdf', [PaymentController::class, 'downloadPdf']);
        Route::post('/{id}/approve', [PaymentController::class, 'approve']);
    });

    // User Management (Superadmin Only)
    Route::middleware('role:superadmin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('user-groups', \App\Http\Controllers\Api\UserGroupController::class);
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
        Route::get('/login-logs', [App\Http\Controllers\Api\LoginLogController::class, 'index']);

        // Data Maintenance
        Route::post('/settings/clear-payroll', [SettingController::class, 'clearPayrollData']);
        Route::get('/settings/db-backup', [SettingController::class, 'backupDatabase']);
        Route::post('/settings/db-import', [SettingController::class, 'importDatabase']);

        // API Key Management
        Route::prefix('api-keys')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\ApiKeyController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\ApiKeyController::class, 'store']);
            Route::put('/{id}/toggle', [App\Http\Controllers\Api\ApiKeyController::class, 'toggleActive']);
            Route::delete('/{id}', [App\Http\Controllers\Api\ApiKeyController::class, 'destroy']);
        });
    });

    Route::middleware('role:superadmin,operator')->group(function () {
        Route::prefix('bkd-recon')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\BkdReconController::class, 'index']);
            Route::post('/upload', [App\Http\Controllers\Api\BkdReconController::class, 'store']);
            Route::get('/summary', [App\Http\Controllers\Api\BkdReconController::class, 'summary']);
            Route::get('/export', [App\Http\Controllers\Api\BkdReconController::class, 'export']);
        });

        // BPJS Rekon
        Route::get('/bpjs-rekon', [BpjsRekonController::class, 'index']);
        Route::get('/bpjs-rekon/ump', [BpjsRekonController::class, 'getUmp']);
    });
    Route::put('/bpjs-rekon/ump', [BpjsRekonController::class, 'updateUmp']);
    Route::get('/bpjs-rekon/export', [BpjsRekonController::class, 'export']);

    // Sumber Dana Settings
    Route::get('/sumber-dana', [SumberDanaSettingController::class, 'index']);
    Route::put('/sumber-dana', [SumberDanaSettingController::class, 'update']);
    Route::put('/sumber-dana/bulk', [SumberDanaSettingController::class, 'bulkUpdate']);

    // Payroll Posting
    Route::get('/payroll-postings', [PayrollPostingController::class, 'index']);
    Route::post('/payroll-postings/post', [PayrollPostingController::class, 'post']);
    Route::post('/payroll-postings/unpost', [PayrollPostingController::class, 'unpost']);

    Route::get('/settings/satker-list', [SatkerController::class, 'index']);

    // Master Pegawai & Keluarga (DBF)
    Route::prefix('master')->group(function () {
        Route::post('/pegawai/import', [DbfImportController::class, 'importMasterPegawai']);
        Route::post('/keluarga/import', [DbfImportController::class, 'importMasterKeluarga']);
        Route::get('/pegawai', [MasterPegawaiController::class, 'index']);
        Route::get('/pegawai/stats', [MasterPegawaiController::class, 'stats']);
        Route::get('/pegawai/export', [MasterPegawaiController::class, 'export']);
        Route::get('/pegawai/template-nik', [MasterPegawaiController::class, 'downloadNikTemplate']);
        Route::get('/pegawai/{id}', [MasterPegawaiController::class, 'show']);
        Route::get('/pegawai/nip/{nip}', [MasterPegawaiController::class, 'showByNip']);
    });

    // SP2D Realization
    Route::post('/sp2d/import', [App\Http\Controllers\Api\Sp2dController::class, 'import']);
    Route::get('/sp2d/status', [App\Http\Controllers\Api\Sp2dController::class, 'getStatus']);
    Route::get('/sp2d/transactions', [App\Http\Controllers\Api\Sp2dController::class, 'getTransactions']);
    Route::get('/sp2d/recon', [App\Http\Controllers\Api\Sp2dController::class, 'getRecon']);
    Route::get('/sp2d/recon/{id}', [App\Http\Controllers\Api\Sp2dController::class, 'getReconDetail']);
    Route::get('/sp2d/export-recon', [App\Http\Controllers\Api\Sp2dController::class, 'exportRecon']);
    Route::post('/sp2d/realizations', [App\Http\Controllers\Api\Sp2dController::class, 'store']);
    Route::put('/sp2d/realizations/{id}', [App\Http\Controllers\Api\Sp2dController::class, 'update']);
    Route::delete('/sp2d/realizations/{id}', [App\Http\Controllers\Api\Sp2dController::class, 'destroy']);

    // Budget
    Route::get('/budgets/comparison', [App\Http\Controllers\Api\BudgetController::class, 'comparisonReport']);
    Route::apiResource('budgets', App\Http\Controllers\Api\BudgetController::class);

    // Tax Status (PTKP)
    Route::get('/tax-status', [App\Http\Controllers\Api\TaxStatusController::class, 'index']);
    Route::post('/tax-status', [App\Http\Controllers\Api\TaxStatusController::class, 'store']);
    Route::post('/tax-status/initialize', [App\Http\Controllers\Api\TaxStatusController::class, 'initializeYear']);
    Route::get('/tax-status/export', [App\Http\Controllers\Api\TaxStatusController::class, 'export']);
    Route::post('/tax-status/import', [App\Http\Controllers\Api\TaxStatusController::class, 'import']);
    // Help Center (Manual Book)
    Route::get('/help', [App\Http\Controllers\HelpArticleController::class, 'index']);
    Route::get('/help/{slug}', [App\Http\Controllers\HelpArticleController::class, 'show']);
    Route::post('/help', [App\Http\Controllers\HelpArticleController::class, 'store']);
    Route::put('/help/{id}', [App\Http\Controllers\HelpArticleController::class, 'update']);
    Route::delete('/help/{id}', [App\Http\Controllers\HelpArticleController::class, 'destroy']);

    // Announcements
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::middleware('role:superadmin')->group(function () {
        Route::get('/announcements/list', [AnnouncementController::class, 'list']);
        Route::post('/announcements', [AnnouncementController::class, 'store']);
        Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update']);
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy']);
    });
    Route::post('/cache/clear', [CacheController::class, 'clear']);
});

// Simgaji Integration API (protected by API Key)
Route::middleware('api.key')->group(function () {
    Route::get('/listinstansi', [App\Http\Controllers\Api\SimgajiController::class, 'listInstansi']);
    Route::get('/listpegawai', [App\Http\Controllers\Api\SimgajiController::class, 'listPegawai']);
    Route::get('/listgaji', [App\Http\Controllers\Api\SimgajiController::class, 'listGaji']);
});
