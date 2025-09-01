<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WithdrawalController;

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

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
