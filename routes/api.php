<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\TrainingSessionController;
use App\Http\Controllers\Api\WorkspaceMemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/users/me', [AuthController::class, 'me'])->middleware('auth:api');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');

Route::middleware('auth:api')->group(function () {
    Route::post('workspaces', [WorkspaceController::class, 'store']);
    Route::get('workspaces', [WorkspaceController::class, 'list']);
    Route::get('workspaces/{workspace}', [WorkspaceController::class, 'show']);
    Route::patch('workspaces/{workspace}', [WorkspaceController::class, 'update']);
    Route::delete('workspaces/{workspace}', [WorkspaceController::class, 'delete']);
});

Route::middleware('auth:api')->group(function () {
    Route::post('training-sessions', [TrainingSessionController::class, 'store']);
    Route::get('training-sessions', [TrainingSessionController::class, 'list']);
    Route::get('training-sessions/{training_session}', [TrainingSessionController::class, 'show']);
    Route::patch('training-sessions/{training_session}', [TrainingSessionController::class, 'update']);
    Route::delete('training-sessions/{training_session}', [TrainingSessionController::class, 'delete']);
});


Route::middleware('auth:api')->group(function () {
    Route::get('workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index']);
});