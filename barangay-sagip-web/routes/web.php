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

// Root always goes to the resident-facing login — this is a citizen-facing
// app first; staff access lives at the separate, unlisted /admin/login below
// and is never linked from here.
Route::get('/', fn () => redirect()->route('login'));

// ---------------------------------------------------------------------
// Resident-facing guest routes
// ---------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ---------------------------------------------------------------------
// Staff-only login — deliberately not linked from any resident-facing
// page. Officials and personnel use this instead of the routes above.
// ---------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('admin/login', [AdminAuthenticatedSessionController::class, 'create'])->name('admin.login');
    Route::post('admin/login', [AdminAuthenticatedSessionController::class, 'store'])->name('admin.login.store');
});

Route::post('admin/logout', [AdminAuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.logout');

// ---------------------------------------------------------------------
// Authenticated routes (all roles)
// ---------------------------------------------------------------------
Route::middleware('auth')->group(function () {

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Feature 1: Resident Registration and Profiling (profile completion/edit)
    Route::get('profile', [ResidentProfileController::class, 'edit'])->name('residents.profile.edit');
    Route::put('profile', [ResidentProfileController::class, 'update'])->name('residents.profile.update');

    // Feature 2/3/4/5/7: request submission + tracking
    Route::get('requests', [EmergencyRequestController::class, 'index'])->name('requests.index');
    Route::get('requests/create', [EmergencyRequestController::class, 'create'])->name('requests.create');
    Route::post('requests', [EmergencyRequestController::class, 'store'])->name('requests.store');
    Route::get('requests/{emergencyRequest}', [EmergencyRequestController::class, 'show'])->name('requests.show');

    // Feature 8: Location Map Generator
    Route::get('map', [MapController::class, 'index'])->name('map.index');
    Route::get('map/data', [MapController::class, 'data'])->name('map.data');

    // Feature 10: Alerts and Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    // Officials & personnel: status updates + manual assignment
    Route::middleware('role:official,personnel')->group(function () {
        Route::patch('requests/{emergencyRequest}/status', [EmergencyRequestController::class, 'updateStatus'])
            ->name('requests.updateStatus');
    });

    // Feature 6: Response Assignment Classification (manual override) — officials only
    Route::middleware('role:official')->group(function () {
        Route::get('requests/{emergencyRequest}/assign', [ResponseAssignmentController::class, 'edit'])->name('requests.assign.edit');
        Route::post('requests/{emergencyRequest}/assign', [ResponseAssignmentController::class, 'store'])->name('requests.assign.store');

        // Feature 9: Response Personnel Management
        Route::get('personnel', [ResponsePersonnelController::class, 'index'])->name('personnel.index');
        Route::get('personnel/create', [ResponsePersonnelController::class, 'create'])->name('personnel.create');
        Route::post('personnel', [ResponsePersonnelController::class, 'store'])->name('personnel.store');
        Route::get('personnel/{personnel}/edit', [ResponsePersonnelController::class, 'edit'])->name('personnel.edit');
        Route::put('personnel/{personnel}', [ResponsePersonnelController::class, 'update'])->name('personnel.update');
        Route::delete('personnel/{personnel}', [ResponsePersonnelController::class, 'destroy'])->name('personnel.destroy');
        Route::post('personnel/{personnel}/toggle-availability', [ResponsePersonnelController::class, 'toggleAvailability'])
            ->name('personnel.toggleAvailability');

        // Feature 12: Report Generator
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');
    });

    // Personnel: update their own live location
    Route::middleware('role:personnel')->group(function () {
        Route::post('personnel/{personnel}/location', [ResponsePersonnelController::class, 'updateLocation'])
            ->name('personnel.updateLocation');
    });
});
