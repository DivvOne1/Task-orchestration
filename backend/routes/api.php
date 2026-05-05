<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'taskflow-api',
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/meta', function () {
    return response()->json([
        'name' => 'TaskFlow',
        'version' => '0.1.0',
        'features' => [
            'laravel-api',
            'vue-frontend',
            'go-notification-service',
            'redis-cache',
            'rabbitmq-events',
            'docker-compose',
        ],
    ]);
});

Route::get('/dashboard/summary', function () {
    try {
        DB::connection()->getPdo();
        $databaseStatus = 'ok';
    } catch (\Throwable) {
        $databaseStatus = 'error';
    }

    return response()->json([
        'status' => 'ok',
        'server_time' => now()->toIso8601String(),
        'database' => $databaseStatus,
        'counts' => [
            'users' => User::count(),
            'projects' => Project::count(),
            'tasks' => Task::count(),
            'comments' => Comment::count(),
            'notifications' => Notification::count(),
        ],
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
});
