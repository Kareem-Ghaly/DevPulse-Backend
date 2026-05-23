<?php

use App\Http\Controllers\AdminUserApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('register/student', [AuthController::class, 'registerStudent']);
    Route::post('register/supervisor', [AuthController::class, 'registerSupervisor']);
    Route::post('register/committee-member', [AuthController::class, 'registerCommitteeMember']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('admin-login', [AuthController::class, 'adminLogin']);
    Route::get('{provider}/redirect', [AuthController::class, 'redirectToProvider']);
    Route::get('{provider}/callback', [AuthController::class, 'handleProviderCallback']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'role:Admin'])
    ->group(function (): void {
        Route::get('users/pending', [AdminUserApprovalController::class, 'pendingUsers']);
        Route::put('users/{user}/approve', [AdminUserApprovalController::class, 'approveUser']);
        Route::put('users/{user}/reject', [AdminUserApprovalController::class, 'rejectUser']);
    });

Route::prefix('profile')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::put('student/complete', [ProfileController::class, 'completeStudentProfile'])->middleware('role:Student');
        Route::put('supervisor/complete', [ProfileController::class, 'completeSupervisorProfile'])->middleware('role:Supervisor');
        Route::put('committee-member/complete', [ProfileController::class, 'completeCommitteeMemberProfile'])->middleware('role:CommitteeMember');
    });
