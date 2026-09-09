<?php

use App\Http\Controllers\MapController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Minimal Sanctum-protected API surface, kept intentionally small for now.
| Extend this if/when a dedicated resident mobile app is built — reuse the
| existing controllers' logic (e.g. move shared logic into the Services
| classes, which the web controllers already do) rather than duplicating it.
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());

    // Feature 8 data feed, usable by a future mobile map view.
    Route::get('/map/data', [MapController::class, 'data']);
});
