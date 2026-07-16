<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Http\Resources\ProjectInvitationResource;
use App\Interfaces\ProjectIdeaRepositoryInterface;
use App\Interfaces\ProjectJoinRequestRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\ProjectJoinRequest;
use App\Models\ProjectTeam;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProjectInvitationService extends BaseService
{
    public function __construct(
        private readonly ProjectTeamService $teamService,
        private readonly ProjectIdeaRepositoryInterface $projectIdeas,
        private readonly ProjectJoinRequestRepositoryInterface $joinRequests,
        private readonly UserRepositoryInterface $users,
        private readonly NotificationService $notifications,
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

        $this->notifications->sendToUser(
            $receiver,
            'Project team invitation',
            "You were invited to join {$projectIdea->title}.",
            [
                'type' => 'team_invitation_sent',
                'entity_type' => 'project_join_request',
                'entity_id' => $invitation->id,
            ]
        );

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

        [$invitation, $team] = DB::transaction(function () use ($invitation, $status): array {
            $invitation = $status === 'accepted'
                ? $this->joinRequests->accept($invitation)
                : $this->joinRequests->reject($invitation);

            $team = null;

            if ($status === 'accepted') {
                $team = $this->teamService->addAcceptedMember($invitation->projectIdea, $invitation->receiver_id);
            }

            return [$invitation, $team];
        });

        $this->notifyInvitationResponse($invitation, $status, $team);

        return $this->successResponse([
            'invitation' => new ProjectInvitationResource($invitation),
        ], "Invitation {$status} successfully");
    }

    private function notifyInvitationResponse(ProjectJoinRequest $invitation, string $status, ?ProjectTeam $team): void
    {
        $invitation->loadMissing(['projectIdea.owner', 'receiver']);
        $owner = $invitation->projectIdea?->owner;

        if ($owner) {
            $this->notifications->sendToUser(
                $owner,
                $status === 'accepted' ? 'Invitation accepted' : 'Invitation rejected',
                "{$invitation->receiver?->name} {$status} your project team invitation.",
                [
                    'type' => $status === 'accepted' ? 'team_invitation_accepted' : 'team_invitation_rejected',
                    'entity_type' => 'project_join_request',
                    'entity_id' => $invitation->id,
                ]
            );
        }

        if ($status === 'accepted' && $team?->status === 'completed') {
            $team->loadMissing(['members.user', 'leader']);
            $members = $team->members->pluck('user')->filter();

            if ($team->leader) {
                $members->push($team->leader);
            }

            $this->notifications->sendToUsers(
                $members,
                'Project team completed',
                'Your project team is now complete.',
                [
                    'type' => 'team_completed',
                    'entity_type' => 'project_team',
                    'entity_id' => $team->id,
                ]
            );
        }
    }
}