<?php

use App\Http\Controllers\Fmcg\BulkUploadController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'fmcg/dashboard')->name('dashboard');

    Route::prefix('fmcg')->name('fmcg.')->group(function () {
        Route::inertia('uploads', 'fmcg/uploads/index')->name('uploads.index');
        Route::inertia('uploads/new', 'fmcg/uploads/new')->name('uploads.new');
        Route::inertia('uploads/validation', 'fmcg/uploads/validation')->name('uploads.validation');
        Route::post('bulk-uploads', [BulkUploadController::class, 'store'])->name('bulk-uploads.store');

        Route::inertia('processing', 'fmcg/processing')->name('processing');
        Route::inertia('approvals', 'fmcg/approvals')->name('approvals');

        Route::inertia('orders', 'fmcg/orders/index')->name('orders.index');
        Route::inertia('orders/so-55012', 'fmcg/orders/show')->name('orders.show');

        Route::inertia('reconciliation', 'fmcg/reconciliation')->name('reconciliation');
        Route::inertia('audit', 'fmcg/audit')->name('audit');

        Route::inertia('settings/pricing-rules', 'fmcg/settings/pricing-rules')->name('settings.pricing-rules');
        Route::inertia('settings/inventory-rules', 'fmcg/settings/inventory-rules')->name('settings.inventory-rules');
        Route::inertia('settings/users-roles', 'fmcg/settings/users-roles')->name('settings.users-roles');
    });
});

require __DIR__.'/settings.php';
