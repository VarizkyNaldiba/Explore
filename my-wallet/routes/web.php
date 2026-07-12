<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WhatsappApiController;
use App\Http\Controllers\EmailConfigController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Artisan;
use App\Models\Setting;

// Public Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Public API routes for WhatsApp Bot (exchanged with background service)
Route::post('/api/whatsapp/status', [WhatsappApiController::class, 'updateStatus'])->name('whatsapp.status.update');
Route::post('/api/whatsapp/qr', [WhatsappApiController::class, 'updateQr'])->name('whatsapp.qr.update');
Route::post('/api/whatsapp/message', [WhatsappApiController::class, 'handleMessage'])->name('whatsapp.message.handle');
Route::get('/api/whatsapp/status-check', function () {
    return response()->json([
        'status' => Setting::getValue('whatsapp_status', 'disconnected'),
        'user' => Setting::getValue('whatsapp_user', null),
        'qr' => Setting::getValue('whatsapp_qr', null),
    ]);
})->name('whatsapp.status.check');

// Protected Web Routes (Requires Authentication)
Route::middleware(['auth'])->group(function () {
    // Dashboard view
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Update admin profile
    Route::post('/profile', [DashboardController::class, 'updateProfile'])->name('profile.update');

    // Manual transaction input
    Route::post('/transaction/manual', [DashboardController::class, 'storeManual'])->name('transaction.manual.save');
    Route::put('/transaction/{id}', [DashboardController::class, 'updateTransaction'])->name('transaction.update');
    Route::delete('/transaction/{id}', [DashboardController::class, 'deleteTransaction'])->name('transaction.delete');

    // WhatsApp manual disconnect
    Route::post('/whatsapp/disconnect', [WhatsappApiController::class, 'disconnect'])->name('whatsapp.disconnect');

    // Save Email Config & Test email connection
    Route::post('/email/config', [EmailConfigController::class, 'saveConfig'])->name('email.config.save');
    Route::post('/api/email/test', [EmailConfigController::class, 'testConnection'])->name('email.config.test');

    // Trigger manual fetch email
    Route::post('/email/fetch', function () {
        Artisan::call('email:fetch');
        return back()->with('success', 'Sinkronisasi email berhasil dijalankan.');
    })->name('email.fetch');
});
