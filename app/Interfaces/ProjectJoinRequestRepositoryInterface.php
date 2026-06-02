<?php

namespace App\Interfaces;

use App\Models\ProjectJoinRequest;
use Illuminate\Support\Collection;

interface ProjectJoinRequestRepositoryInterface
{
    public function create(array $data): ProjectJoinRequest;

    public function findById(int $id): ?ProjectJoinRequest;

    public function findPendingById(int $id): ?ProjectJoinRequest;

    public function getReceivedInvitations(int $userId): Collection;

    public function getProjectRequests(int $projectIdeaId): Collection;

    public function hasDuplicateInvitation(int $projectIdeaId, int $senderId, int $receiverId): bool;

    public function accept(ProjectJoinRequest $request): ProjectJoinRequest;

    public function reject(ProjectJoinRequest $request): ProjectJoinRequest;

    public function cancel(ProjectJoinRequest $request): ProjectJoinRequest;
}
