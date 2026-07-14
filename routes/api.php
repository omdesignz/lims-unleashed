<?php

use App\Http\Controllers\Api\IntegrationIngestController;
use App\Http\Controllers\Api\UserThemeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->patch('/user/theme', [UserThemeController::class, 'update']);

Route::prefix('integrations/v1/connectors/{connector}')
    ->name('api.integrations.')
    ->middleware('throttle:integration-ingest')
    ->group(function (): void {
        Route::post('ingest', [IntegrationIngestController::class, 'store'])->name('ingest');
        Route::post('heartbeat', [IntegrationIngestController::class, 'heartbeat'])->name('heartbeat');
    });
