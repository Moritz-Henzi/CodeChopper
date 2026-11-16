<?php

use App\Http\Controllers\Api\V1\DashboardMatrixController;
use App\Http\Controllers\Api\V1\TrainingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->middleware('auth:sanctum')->name('api.v1.')->group(function () {
    Route::get('dashboard/matrix', DashboardMatrixController::class)->name('dashboard.matrix');
    Route::get('trainings', [TrainingController::class, 'index'])->name('trainings.index');
    Route::get('trainings/{training}', [TrainingController::class, 'show'])->name('trainings.show');
    Route::get('crew-members/{user}', [DashboardMatrixController::class, 'show'])->name('crew-members.show');
});
