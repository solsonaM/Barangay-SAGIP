<?php

use App\Http\Controllers\Admin\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmergencyRequestController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResidentProfileController;
use App\Http\Controllers\ResponseAssignmentController;
use App\Http\Controllers\ResponsePersonnelController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])->middleware('throttle:login');
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')->name('logout');

Route::middleware('guest')->group(function () {
    Route::get('admin/login', [AdminAuthenticatedSessionController::class, 'create'])->name('admin.login');
    Route::post('admin/login', [AdminAuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')->name('admin.login.store');
});

Route::post('admin/logout', [AdminAuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')->name('admin.logout');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('profile', [ResidentProfileController::class, 'edit'])->name('residents.profile.edit');
    Route::put('profile', [ResidentProfileController::class, 'update'])->name('residents.profile.update');
    Route::get('requests', [EmergencyRequestController::class, 'index'])->name('requests.index');
    Route::get('requests/create', [EmergencyRequestController::class, 'create'])->name('requests.create');
    Route::post('requests', [EmergencyRequestController::class, 'store'])->name('requests.store');
    Route::get('requests/{emergencyRequest}', [EmergencyRequestController::class, 'show'])->name('requests.show');
    Route::get('map', [MapController::class, 'index'])->name('map.index');
    Route::get('map/data', [MapController::class, 'data'])->name('map.data');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    Route::middleware('role:official,personnel')->group(function () {
        Route::patch('requests/{emergencyRequest}/status', [EmergencyRequestController::class, 'updateStatus'])
            ->name('requests.updateStatus');
    });

    Route::middleware('role:official')->group(function () {
        Route::get('requests/{emergencyRequest}/assign', [ResponseAssignmentController::class, 'edit'])->name('requests.assign.edit');
        Route::post('requests/{emergencyRequest}/assign', [ResponseAssignmentController::class, 'store'])->name('requests.assign.store');
        Route::get('personnel', [ResponsePersonnelController::class, 'index'])->name('personnel.index');
        Route::get('personnel/create', [ResponsePersonnelController::class, 'create'])->name('personnel.create');
        Route::post('personnel', [ResponsePersonnelController::class, 'store'])->name('personnel.store');
        Route::get('personnel/{personnel}/edit', [ResponsePersonnelController::class, 'edit'])->name('personnel.edit');
        Route::put('personnel/{personnel}', [ResponsePersonnelController::class, 'update'])->name('personnel.update');
        Route::delete('personnel/{personnel}', [ResponsePersonnelController::class, 'destroy'])->name('personnel.destroy');
        Route::post('personnel/{personnel}/toggle-availability', [ResponsePersonnelController::class, 'toggleAvailability'])
            ->name('personnel.toggleAvailability');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');
    });

    // Personnel may update only their own live location.
    Route::middleware('role:personnel')->group(function () {
        Route::post('personnel/location', [ResponsePersonnelController::class, 'updateLocation'])
            ->name('personnel.updateLocation');
    });
});
