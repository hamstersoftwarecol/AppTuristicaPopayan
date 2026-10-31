<?php

use App\Enums\Role;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\RequirePrivilegedTwoFactor;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth', 'verified', RequirePrivilegedTwoFactor::class])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/{role}', [DashboardController::class, 'show'])
        ->whereIn('role', array_column(Role::cases(), 'value'))
        ->name('dashboard.role');
});

require __DIR__.'/settings.php';

require __DIR__.'/operations.php';
