<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentEnrollmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CourseController;

// ===== PUBLIC ROUTES =====

// Landing page
Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return view('welcome');
})->name('landing');

// Login page (show form)
Route::get('/login', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return view('auth.login');
})->name('login');

// Login submission
Route::post('/login', [AuthController::class, 'login']);
Route::post('/api/login', [AuthController::class, 'login']);

// Logout
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
Route::post('/api/logout', [AuthController::class, 'logout'])->middleware('auth');

// ===== PROTECTED ROUTES =====

Route::middleware(['auth'])->group(function () {

    // ==========================================================================
    // DASHBOARD
    // ==========================================================================
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Dashboard Stats API
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/api/dashboard/stats', [DashboardController::class, 'stats']);

    // ==========================================================================
    // Reports
    // ==========================================================================
    Route::get('/reports/generate', [ReportController::class, 'generate']);
    Route::get('/api/reports/generate', [ReportController::class, 'generate']);

    // ==========================================================================
    // Student Enrollment & Management
    // ==========================================================================
    Route::controller(StudentEnrollmentController::class)->group(function () {

        Route::get('/enroll', 'index')->name('enroll.index');
        Route::get('/students', 'index')->name('students.index');

        Route::post('/enroll-student', 'enroll')->name('enroll.store');
        Route::post('/api/enroll-student', 'enroll');

        Route::get('/students/archived', 'getArchivedStudents');
        Route::get('/api/students/archived', 'getArchivedStudents');

        Route::get('/students-json', 'getStudentsJson')->name('students.json');
        Route::get('/api/students', 'getStudentsJson');

        Route::put('/students/{id}', 'update');
        Route::put('/api/students/{id}', 'update');

        Route::delete('/students/{id}/archive', 'archive');
        Route::post('/students/{id}/archive', 'archive');
        Route::delete('/api/students/{id}/archive', 'archive');
        Route::post('/api/students/{id}/archive', 'archive');

        Route::post('/students/{id}/restore', 'restore');
        Route::post('/api/students/{id}/restore', 'restore');

        Route::delete('/students/{id}/force-delete', 'forceDelete');
        Route::post('/students/{id}/force-delete', 'forceDelete');
        Route::delete('/api/students/{id}/force-delete', 'forceDelete');
        Route::post('/api/students/{id}/force-delete', 'forceDelete');

        Route::get('/custom-fields', 'getCustomFields');
        Route::get('/api/custom-fields', 'getCustomFields');
        Route::post('/custom-fields', 'addCustomField');
        Route::post('/api/custom-fields', 'addCustomField');
        Route::delete('/custom-fields/{id}', 'removeCustomField');
        Route::delete('/api/custom-fields/{id}', 'removeCustomField');
    });

    // ==========================================================================
    // Payment Processing
    // ==========================================================================
    Route::controller(PaymentController::class)->group(function () {

        Route::post('/payments', 'store');
        Route::post('/api/payments', 'store');

        Route::get('/payments/pending', 'getPendingPayments');
        Route::get('/api/payments/pending', 'getPendingPayments');

        Route::match(['post', 'put'], '/payments/{id}/approve', 'approve');
        Route::match(['post', 'put'], '/api/payments/{id}/approve', 'approve');

        Route::match(['post', 'put'], '/payments/{id}/reject', 'reject');
        Route::match(['post', 'put'], '/api/payments/{id}/reject', 'reject');

        Route::get('/payments/rejected', 'getRejectedPayments');
        Route::get('/api/payments/rejected', 'getRejectedPayments');

        Route::get('/payments/rejection-logs', 'rejectionLogs');
        Route::get('/api/payments/rejection-logs', 'rejectionLogs');

        Route::match(['put', 'post'], '/payments/{id}/resubmit', 'resubmit');
        Route::match(['put', 'post'], '/api/payments/{id}/resubmit', 'resubmit');

        Route::get('/payments/approved', 'getApprovedPayments');
        Route::get('/api/payments/approved', 'getApprovedPayments');

        Route::get('/students/{studentId}/payments', 'getStudentPaymentHistory');
        Route::get('/api/students/{studentId}/payments', 'getStudentPaymentHistory');
    });

    // ==========================================================================
    // Fees
    // ==========================================================================
    Route::put('/fees/{id}', [FeeController::class, 'update']);
    Route::put('/api/fees/{id}', [FeeController::class, 'update']);

    Route::get('/students/{studentId}/fees', [FeeController::class, 'getStudentFees']);
    Route::get('/api/students/{studentId}/fees', [FeeController::class, 'getStudentFees']);

    Route::post('/students/{studentId}/fees', [FeeController::class, 'store']);
    Route::post('/api/students/{studentId}/fees', [FeeController::class, 'store']);

    Route::delete('/fees/{id}', [FeeController::class, 'destroy']);
    Route::delete('/api/fees/{id}', [FeeController::class, 'destroy']);

    // ==========================================================================
    // Users
    // ==========================================================================
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/api/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::post('/api/users', [UserController::class, 'store']);
    Route::put('/users/{user}/role', [UserController::class, 'updateRole']);
    Route::put('/api/users/{user}/role', [UserController::class, 'updateRole']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::delete('/api/users/{user}', [UserController::class, 'destroy']);
});