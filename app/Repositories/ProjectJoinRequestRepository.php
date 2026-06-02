<?php

namespace App\Repositories;

use App\Interfaces\ProjectJoinRequestRepositoryInterface;
use App\Models\ProjectJoinRequest;
use Illuminate\Support\Collection;

class ProjectJoinRequestRepository implements ProjectJoinRequestRepositoryInterface
{
    public function create(array $data): ProjectJoinRequest
    {
        $request = ProjectJoinRequest::query()->create($data);

        return $request->fresh(['projectIdea.owner', 'sender', 'receiver']);
    }

    public function findById(int $id): ?ProjectJoinRequest
    {
        return ProjectJoinRequest::query()
            ->with(['projectIdea.owner', 'sender', 'receiver'])
            ->find($id);
    }

    public function findPendingById(int $id): ?ProjectJoinRequest
    {
        return ProjectJoinRequest::query()
            ->with(['projectIdea.team', 'sender', 'receiver'])
            ->where('status', 'pending')
            ->find($id);
    }

    public function getReceivedInvitations(int $userId): Collection
    {
        return ProjectJoinRequest::query()
            ->with(['projectIdea.owner', 'sender', 'receiver'])
            ->where('receiver_id', $userId)
            ->latest()
            ->get();
    }

    public function getProjectRequests(int $projectIdeaId): Collection
    {
        return ProjectJoinRequest::query()
            ->with(['sender', 'receiver'])
            ->where('project_idea_id', $projectIdeaId)
            ->latest()
            ->get();
    }

    public function hasDuplicateInvitation(int $projectIdeaId, int $senderId, int $receiverId): bool
    {
        return ProjectJoinRequest::query()
            ->where('project_idea_id', $projectIdeaId)
            ->where('receiver_id', $receiverId)
            ->exists();
    }

    public function accept(ProjectJoinRequest $request): ProjectJoinRequest
    {
        $request->update(['status' => 'accepted']);

        return $request->fresh(['projectIdea.owner', 'sender', 'receiver']);
    }

    public function reject(ProjectJoinRequest $request): ProjectJoinRequest
    {
        $request->update(['status' => 'rejected']);

        return $request->fresh(['projectIdea.owner', 'sender', 'receiver']);
    }

    public function cancel(ProjectJoinRequest $request): ProjectJoinRequest
    {
        $request->update(['status' => 'cancelled']);

        return $request->fresh(['projectIdea.owner', 'sender', 'receiver']);
    }
}
