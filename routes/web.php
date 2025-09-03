<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\ReferralController; 

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Route::get('dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard', [DashboardController::class, 'show'], function () {
    return Inertia::render('Dashboard');
})
    ->middleware(['auth']) // <--- SOLO DEJA 'auth'
    ->name('dashboard');

Route::get('/my-referrals', [ReferralController::class, 'index'])
    ->middleware(['auth']) // Protegida para usuarios logueados y verificados
    ->name('referrals.index');

// Grupo de rutas para el ADMIN
// Protegido por 'auth' y por nuestro middleware 'is.admin'
Route::middleware(['auth', 'is.admin'])->name('admin.')->prefix('admin')->group(function () {
    Route::resource('users', UserController::class)->except(['show', 'edit', 'create']);
    Route::resource('transactions', TransactionController::class)->except(['show', 'edit', 'create']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/select-plan', [PlanController::class, 'selection'])->name('plan.selection');
    Route::post('/select-plan', [PlanController::class, 'store'])->name('plan.store');
});

// Actualiza la ruta del dashboard para usar el nuevo middleware
Route::get('/dashboard', [DashboardController::class, 'show'])
    ->middleware(['auth', 'verified', 'plan.selected']) // <-- AÑADE 'plan.selected'
    ->name('dashboard');

Route::post('/subscriptions', [SubscriptionController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('subscriptions.store');

Route::post('/withdrawals', [WithdrawalController::class, 'store'])
    ->middleware(['auth'])
    ->name('withdrawals.store');

Route::middleware(['auth', 'is.admin'])->prefix('admin')->name('admin.')->group(function () {
    // Ruta para mostrar la lista de suscripciones pendientes
    Route::get('/subscriptions/pending', [AdminSubscriptionController::class, 'pending'])
        ->name('subscriptions.pending');

    // Ruta para aprobar una suscripción
    Route::patch('/subscriptions/{subscription}/approve', [AdminSubscriptionController::class, 'approve'])
        ->name('subscriptions.approve');

    Route::get('/withdrawals', [AdminWithdrawalController::class, 'index'])
        ->name('withdrawals.index');

    Route::patch('/withdrawals/{withdrawal}/complete', [AdminWithdrawalController::class, 'complete'])
        ->name('withdrawals.complete');

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
         ->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
