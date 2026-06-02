<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Http\Resources\ProjectInvitationResource;
use App\Interfaces\ProjectIdeaRepositoryInterface;
use App\Interfaces\ProjectJoinRequestRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\ProjectJoinRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProjectInvitationService extends BaseService
{
    public function __construct(
        private readonly ProjectTeamService $teamService,
        private readonly ProjectIdeaRepositoryInterface $projectIdeas,
        private readonly ProjectJoinRequestRepositoryInterface $joinRequests,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function send(int $projectIdeaId, array $data): JsonResponse
    {
        $projectIdea = $this->projectIdeas->findOwnedByUser($projectIdeaId, auth()->id());

        if (! $projectIdea) {
            return $this->errorResponse('Only the project owner can send invitations.', null, 403);
        }

        if (in_array($projectIdea->status, ['team_completed', 'closed'], true) || $this->teamService->isFull($projectIdea)) {
            return $this->errorResponse('Completed ideas cannot receive invitations.', null, 422);
        }

        $receiverId = (int) $data['receiver_id'];

        if ($receiverId === $projectIdea->owner_id) {
            return $this->errorResponse('Owner cannot invite himself.', null, 422);
        }

        $receiver = $this->users->findUserById($receiverId);

        if (! $receiver?->hasRole(UserRole::Student->value)) {
            return $this->errorResponse('Only students can be invited to a project team.', null, 422);
        }

        if ($this->joinRequests->hasDuplicateInvitation($projectIdea->id, auth()->id(), $receiverId)) {
            return $this->errorResponse('This student already has an invitation for this project.', null, 409);
        }

        $invitation = $this->joinRequests->create([
            'project_idea_id' => $projectIdea->id,
            'sender_id' => auth()->id(),
            'receiver_id' => $receiverId,
            'status' => 'pending',
        ]);

        return $this->successResponse([
            'invitation' => new ProjectInvitationResource($invitation),
        ], 'Project invitation sent successfully', 201);
    }

    public function myInvitations(): JsonResponse
    {
        $invitations = $this->joinRequests->getReceivedInvitations(auth()->id());

        return $this->successResponse([
            'invitations' => ProjectInvitationResource::collection($invitations),
        ], 'Invitations retrieved successfully');
    }

    public function accept(int $invitationId): JsonResponse
    {
        return $this->respondToInvitation($invitationId, 'accepted');
    }

    public function reject(int $invitationId): JsonResponse
    {
        return $this->respondToInvitation($invitationId, 'rejected');
    }

    private function respondToInvitation(int $invitationId, string $status): JsonResponse
    {
        $invitation = $this->joinRequests->findPendingById($invitationId);

        if (! $invitation) {
            return $this->errorResponse('Pending invitation not found.', null, 404);
        }

        if ($invitation->receiver_id !== auth()->id()) {
            return $this->errorResponse('Only the invitation receiver can respond to it.', null, 403);
        }

        if ($status === 'accepted' && $this->teamService->isFull($invitation->projectIdea)) {
            return $this->errorResponse('This project team is already full.', null, 422);
        }

        $invitation = DB::transaction(function () use ($invitation, $status): ProjectJoinRequest {
            $invitation = $status === 'accepted'
                ? $this->joinRequests->accept($invitation)
                : $this->joinRequests->reject($invitation);

            if ($status === 'accepted') {
                $this->teamService->addAcceptedMember($invitation->projectIdea, $invitation->receiver_id);
            }

            return $invitation;
        });

        return $this->successResponse([
            'invitation' => new ProjectInvitationResource($invitation),
        ], "Invitation {$status} successfully");
    }
}
