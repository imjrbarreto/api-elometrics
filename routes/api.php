<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TrainingGameController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\TrainingSessionController;
use App\Http\Controllers\Api\TrainingSessionResponseController;
use App\Http\Controllers\Api\WorkspaceMemberController;
use App\Http\Controllers\Api\WorkspaceTrainingSessionController;
use App\Http\Controllers\Api\WorkspaceTrainingSessionExportController;
use Illuminate\Support\Facades\Route;


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
    Route::post('training-sessions/{training_session}/games', [TrainingGameController::class, 'store']);
    // Route::delete('training-sessions/{training_session}', [TrainingSessionController::class, 'delete']);
});


Route::middleware('auth:api')->group(function () {
    Route::get('workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index']);
    Route::post('workspaces/{workspace}/members', [WorkspaceMemberController::class, 'store']);
    Route::delete('workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'destroy']);
});

Route::middleware('auth:api')->group(function () {
    Route::post('workspaces/{workspace}/training-sessions', [WorkspaceTrainingSessionController::class, 'store']);
    Route::get('workspaces/{workspace}/training-sessions', [WorkspaceTrainingSessionController::class, 'index']);
    Route::get('workspaces/{workspace}/training-sessions/{training_session}', [WorkspaceTrainingSessionController::class, 'show']);
    Route::delete('workspaces/{workspace}/training-sessions/{training_session}', [WorkspaceTrainingSessionController::class, 'destroy']);
    });

Route::middleware('auth:api')->group(function () {
    Route::post('workspaces/{workspace}/training-sessions/{training_session}/responses', [TrainingSessionResponseController::class, 'store']);
    Route::get('workspaces/{workspace}/training-sessions/{training_session}/responses', [TrainingSessionResponseController::class, 'index']);
    Route::get('workspaces/{workspace}/training-sessions/{training_session}/export', WorkspaceTrainingSessionExportController::class);

});