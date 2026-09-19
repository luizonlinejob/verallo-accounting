<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Note: Angular/Vue Axios requests sending to '/api/...' will land here.
| Middleware sanctum is applied. If testing without Sanctum tokens yet,
| you can temporarily remove 'auth:sanctum'.
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    
    // 1. Encoder, Staff, Accounting, Admin, Superadmin: Manage Students
    Route::middleware(['role:superadmin,admin,accounting,staff,encoder'])->group(function () {
        Route::post('/students', [StudentController::class, 'store']);
        Route::get('/students/{id}', [StudentController::class, 'show']);
    });

    // 2. Encoder, Staff, Accounting, Admin, Superadmin: Encoding & Resubmitting Payments
    Route::middleware(['role:superadmin,admin,accounting,staff,encoder'])->group(function () {
        Route::post('/payments', [PaymentController::class, 'store']); // Encode bag-ong payment
        Route::get('/payments/rejected', [PaymentController::class, 'getRejectedPayments']); // Get rejected list
        
        // Suportahan ang PUT ug POST para sa Resubmit
        Route::put('/payments/{id}/resubmit', [PaymentController::class, 'resubmit']);
        Route::post('/payments/{id}/resubmit', [PaymentController::class, 'resubmit']);
    });

    // 3. Admin, Superadmin, Accounting: Approving & Rejecting Payments
    Route::middleware(['role:superadmin,admin,accounting'])->group(function () {
        Route::get('/payments/pending', [PaymentController::class, 'getPendingPayments']); // View all pending
        
        // Suportahan ang PUT ug POST para sa Approve/Reject para dili mag-405 Method Not Allowed
        Route::put('/payments/{id}/approve', [PaymentController::class, 'approve']);
        Route::post('/payments/{id}/approve', [PaymentController::class, 'approve']);

        Route::put('/payments/{id}/reject', [PaymentController::class, 'reject']);
        Route::post('/payments/{id}/reject', [PaymentController::class, 'reject']);
    });

    // 4. Admin, Superadmin, Accounting: View Reports
    Route::middleware(['role:superadmin,admin,accounting'])->group(function () {
        Route::get('/reports/collections', [ReportController::class, 'collectionReport']);
        Route::get('/reports/receivables', [ReportController::class, 'receivablesReport']);
    });

});