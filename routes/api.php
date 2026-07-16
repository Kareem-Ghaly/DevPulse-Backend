<?php

use App\Http\Controllers\AdminUserApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommitteeProjectProposalController;
use App\Http\Controllers\FirebaseTokenController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectIdeaController;
use App\Http\Controllers\ProjectInvitationController;
use App\Http\Controllers\ProjectProposalController;
use App\Http\Controllers\ProjectTeamController;
use App\Http\Controllers\SupervisorTaskReviewController;
use App\Http\Controllers\TaskController;
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

Route::middleware('auth:sanctum')->group(function (): void {

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/firebase-token', [FirebaseTokenController::class, 'store']);
    Route::delete('notifications/firebase-token', [FirebaseTokenController::class, 'destroy']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::apiResource('project-ideas', ProjectIdeaController::class)->parameters([
        'project-ideas' => 'projectIdea',
    ])->middlewareFor('store', 'role:Student');

    Route::post('project-ideas/{projectIdea}/publish', [ProjectIdeaController::class, 'publish']);
    Route::get('project-ideas/{projectIdea}/matching/students', [ProjectIdeaController::class, 'matchingStudents']);
    Route::get('project-ideas/{projectIdea}/matching/supervisors', [ProjectIdeaController::class, 'matchingSupervisors']);
    Route::get('project-ideas/{projectIdea}/team', [ProjectTeamController::class, 'show']);

    Route::post('project-ideas/{projectIdea}/invitations', [ProjectInvitationController::class, 'send']);
    Route::get('my-invitations', [ProjectInvitationController::class, 'myInvitations']);
    Route::post('invitations/{invitation}/accept', [ProjectInvitationController::class, 'accept']);
    Route::post('invitations/{invitation}/reject', [ProjectInvitationController::class, 'reject']);
    Route::get('/my-projects', [ProjectTeamController::class, 'myProjects']);
    Route::get('project-teams/{projectTeam}/members', [TaskController::class, 'members']);
    Route::get('project-teams/{projectTeam}/tasks', [TaskController::class, 'index']);
    Route::post('project-teams/{projectTeam}/tasks', [TaskController::class, 'store']);

    Route::get('tasks/{task}', [TaskController::class, 'show']);
    Route::put('tasks/{task}', [TaskController::class, 'update']);
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus']);
    Route::delete('tasks/{task}', [TaskController::class, 'destroy']);

    Route::post('tasks/{task}/attachments', [TaskController::class, 'addAttachments']);
    Route::delete('task-attachments/{attachment}', [TaskController::class, 'deleteAttachment']);
    Route::post('tasks/{task}/status', [TaskController::class, 'updateStatus']);

    Route::post('tasks/{task}/links', [TaskController::class, 'addLink']);
    Route::delete('task-links/{link}', [TaskController::class, 'deleteLink']);

    Route::get('project-proposals', [ProjectProposalController::class, 'index']);
    Route::get('project-proposals/{projectProposal}', [ProjectProposalController::class, 'show']);

    Route::middleware('role:Student')->group(function (): void {
        Route::post('project-proposals', [ProjectProposalController::class, 'store']);
        Route::put('project-proposals/{projectProposal}', [ProjectProposalController::class, 'update']);
        Route::patch('project-proposals/{projectProposal}', [ProjectProposalController::class, 'update']);
        Route::delete('project-proposals/{projectProposal}', [ProjectProposalController::class, 'destroy']);
        Route::post('project-proposals/{projectProposal}/submit', [ProjectProposalController::class, 'submitToSupervisor']);
        Route::post('project-proposals/{projectProposal}/submit-to-committee', [ProjectProposalController::class, 'submitToCommittee']);
    });

    Route::middleware('role:Supervisor')->group(function (): void {
        Route::get('supervisor/project-proposals', [ProjectProposalController::class, 'supervisorIncoming']);
        Route::post('project-proposals/{projectProposal}/decision', [ProjectProposalController::class, 'supervisorDecision']);

        Route::prefix('supervisor')->group(function (): void {
            Route::get('project-teams/{projectTeam}/tasks', [SupervisorTaskReviewController::class, 'index']);
            Route::post('tasks/{task}/review', [SupervisorTaskReviewController::class, 'review']);
        });
    }); 
    Route::middleware('role:CommitteeMember')->group(function (): void {
        Route::get('committee/project-proposals', [CommitteeProjectProposalController::class, 'index']);
        Route::post('committee/project-proposals/{projectProposal}/decision', [CommitteeProjectProposalController::class, 'decision']);
    });
});