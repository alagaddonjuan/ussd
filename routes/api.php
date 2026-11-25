<?php

use App\Http\Controllers\UssdCallbackController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (/api/*)
|--------------------------------------------------------------------------
*/
Route::get('/health', function () {
    try {
        DB::select('SELECT 1');

        return response()->json([
            'status' => 'OK',
            'database' => 'Connected',
            'timestamp' => now()->toIso8601String(),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'ERROR',
            'database' => 'Disconnected',
        ], 503);
    }
})->name('api.health');

Route::prefix('v1/ussd')->group(function () {
    Route::post('/', [UssdCallbackController::class, 'handle']);
    Route::post('/events', [UssdCallbackController::class, 'events']);
});

Route::post('/ussd', [UssdCallbackController::class, 'handle']);
Route::post('/ussd/events', [UssdCallbackController::class, 'events']);
