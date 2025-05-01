<?php

use App\Http\Controllers\Dashboard\DownloadExportController;
use App\Http\Controllers\Dashboard\ParticipantController;
use App\Http\Controllers\Dashboard\PaymentController;
use App\Http\Controllers\Dashboard\RoleManagementController;
use App\Http\Controllers\Dashboard\SeminarController;
use App\Http\Controllers\Dashboard\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('dashboard/Index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::group([
    'middleware' => ['auth', 'verified'],
    'prefix' => 'dashboard',
    'as' => 'dashboard.',
], function () {
    Route::get('role-management/data', [RoleManagementController::class, 'data'])->name('role-management.data');
    Route::post('role-management/bulk-destroy', [RoleManagementController::class, 'bulkDestroy'])->name('role-management.bulk-destroy');
    Route::resource('role-management', RoleManagementController::class)->only(['index', 'store', 'update', 'destroy', 'show']);

    Route::get('users/data', [UserController::class, 'data'])->name('users.data');
    Route::post('users/bulk-destroy', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy', 'show']);

    Route::get('seminars/data', [SeminarController::class, 'data'])->name('seminar.data');
    Route::post('seminars/bulk-destroy', [SeminarController::class, 'bulkDestroy'])->name('seminar.bulk-destroy');
    Route::post('seminars/{id}/update-status', [SeminarController::class, 'updateStatus'])->name('seminar.update-status');
    Route::post('seminars/export', [SeminarController::class, 'export'])->name('seminar.export');
    Route::post('seminars/import', [SeminarController::class, 'import'])->name('seminar.download-template');
    Route::resource('seminars', SeminarController::class)->only(['index', 'store', 'update', 'destroy', 'show', 'create']);

    Route::get('participants/data', [ParticipantController::class, 'data'])->name('participant.data');
    Route::post('participants/bulk-destroy', [ParticipantController::class, 'bulkDestroy'])->name('participant.bulk-destroy');
    Route::post('participants/{id}/update-status', [ParticipantController::class, 'updateStatus'])->name('participant.update-status');
    Route::post('participants/export', [ParticipantController::class, 'export'])->name('participants.export');
    Route::post('participants/import', [ParticipantController::class, 'import'])->name('participants.download-template');
    Route::resource('participants', ParticipantController::class)->only(['index', 'store', 'update', 'destroy', 'show', 'create']);

    Route::get('payments/data', [PaymentController::class, 'data'])->name('payments.data');
    Route::post('payments/bulk-destroy', [PaymentController::class, 'bulkDestroy'])->name('payments.bulk-destroy');
    Route::post('payments/{id}/update-status', [PaymentController::class, 'updateStatus'])->name('payments.update-status');
    Route::post('payments/export', [PaymentController::class, 'export'])->name('payments.export');
    Route::post('payments/import', [PaymentController::class, 'import'])->name('payments.download-template');
    Route::resource('payments', PaymentController::class);
    
    Route::get('audits', [\App\Http\Controllers\Dashboard\AuditController::class, 'index'])->name('audits.index');
    Route::get('audits/data/{slug?}/{id?}', [\App\Http\Controllers\Dashboard\AuditController::class, 'data'])->name('audits.data');
    
    Route::get('downloads', [DownloadExportController::class, 'index'])->name('downloads.index');
    Route::get('downloads/data', [DownloadExportController::class, 'data'])->name('downloads.data');
    Route::get('downloads/{id}/download', [DownloadExportController::class, 'download'])->name('downloads.download');
    Route::delete('downloads/{id}', [DownloadExportController::class, 'destroy'])->name('downloads.delete');
    Route::post('downloads/bulk-destroy', [DownloadExportController::class, 'bulkDestroy'])->name('downloads.bulk-destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
