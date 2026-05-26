<?php

use App\Enums\Level;
use App\Http\Controllers\Fmcg\BulkUploadController;
use App\Http\Controllers\Fmcg\IntegrationWebhookController;
use App\Http\Controllers\Fmcg\OrderApprovalController;
use App\Http\Controllers\Fmcg\OrderController;
use App\Http\Controllers\Fmcg\ReconciliationController;
use App\Models\BulkUpload;
use App\Services\Fmcg\BulkUploadService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::post('integrations/webhooks/order-sync', [IntegrationWebhookController::class, 'orderSync'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('integrations.webhooks.order-sync');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'fmcg/dashboard')->name('dashboard');

    Route::prefix('fmcg')->name('fmcg.')->group(function () {
        Route::middleware('can:access-operations')->group(function () {
            Route::inertia('uploads', 'fmcg/uploads/index')->name('uploads.index');
            Route::get('uploads/new/{upload?}', function (?BulkUpload $upload, BulkUploadService $service) {
                $metadata = $upload ? $service->getCsvMetadata($upload) : [];

                return Inertia::render('fmcg/uploads/new', [
                    'upload' => $upload,
                    'headers' => $metadata['headers'] ?? null,
                    'sampleData' => $metadata['sampleData'] ?? null,
                ]);
            })->name('uploads.new');
            Route::get('uploads/{upload}/validation', [BulkUploadController::class, 'validation'])->name('uploads.validation');
            Route::post('bulk-uploads', [BulkUploadController::class, 'store'])->name('bulk-uploads.store');
            // Classical fix‑and‑re‑upload: download original CSV with errors
            Route::get('uploads/{upload}/download', [BulkUploadController::class, 'downloadOriginal'])->name('uploads.download');
            // Upload a corrected CSV and start a fresh validation run
            Route::post('uploads/{upload}/replace', [BulkUploadController::class, 'replace'])->name('uploads.replace');
            Route::post('bulk-uploads/{upload}/process-mapping', [BulkUploadController::class, 'processMapping'])->name('bulk-uploads.process-mapping');
            Route::post('bulk-uploads/{upload}/process', [BulkUploadController::class, 'process'])->name('bulk-uploads.process');
            Route::get('uploads/{upload}/download-failed', [BulkUploadController::class, 'downloadFailedRows'])->name('uploads.downloadFailed');

            Route::inertia('processing', 'fmcg/processing')->name('processing');
        });

        Route::middleware('can:access-commercial-review')->group(function () {
            Route::get('approvals', [OrderApprovalController::class, 'index'])->name('approvals');
            Route::post('approvals/{order}/approve', [OrderApprovalController::class, 'approve'])->name('approvals.approve');
            Route::post('approvals/{order}/reject', [OrderApprovalController::class, 'reject'])->name('approvals.reject');

            Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');

            Route::get('reconciliation', [ReconciliationController::class, 'index'])->name('reconciliation');
            Route::post('reconciliation/{integration}/retry', [ReconciliationController::class, 'retry'])->name('reconciliation.retry');
        });

        Route::middleware('can:access-admin-settings')->group(function () {
            Route::get('audit', [App\Http\Controllers\Fmcg\AuditLogController::class, 'index'])->name('audit');

            Route::inertia('settings/pricing-rules', 'fmcg/settings/pricing-rules')->name('settings.pricing-rules');
            Route::inertia('settings/inventory-rules', 'fmcg/settings/inventory-rules')->name('settings.inventory-rules');
            Route::get('settings/users-roles', [App\Http\Controllers\Fmcg\TeamSettingsController::class, 'index'])->name('settings.users-roles');
            Route::put('settings/users-roles/{user}', [App\Http\Controllers\Fmcg\TeamSettingsController::class, 'update'])->name('settings.users-roles.update');
        });
    });
});

require __DIR__.'/settings.php';
