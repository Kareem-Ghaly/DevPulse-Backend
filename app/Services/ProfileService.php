<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Http\Resources\UserResource;
use App\Interfaces\ProfileRepositoryInterface;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ProfileService extends BaseService
{
    public function __construct(private readonly ProfileRepositoryInterface $profiles) {}

    public function completeStudentProfile(array $data): JsonResponse
    {
        $user = auth()->user();

        if (! $this->hasRole($user, UserRole::Student)) {
            return $this->errorResponse('This profile route is not available for your role.', [
                'role' => ['Student profile completion is only available to students.'],
            ], 403);
        }

        $user = $this->profiles->updateStudentProfile($user, $data);
        $user = $this->profiles->markProfileCompleted($user);

        return $this->successResponse([
            'user' => new UserResource($user),
        ], 'Profile completed successfully');
    }

    public function completeSupervisorProfile(array $data): JsonResponse
    {
        $user = auth()->user();

        if (! $this->hasRole($user, UserRole::Supervisor)) {
            return $this->errorResponse('This profile route is not available for your role.', [
                'role' => ['Supervisor profile completion is only available to supervisors.'],
            ], 403);
        }

        $user = $this->profiles->updateSupervisorProfile($user, $data);
        $user = $this->profiles->markProfileCompleted($user);

        return $this->successResponse([
            'user' => new UserResource($user),
        ], 'Profile completed successfully');
    }

    public function completeCommitteeMemberProfile(array $data): JsonResponse
    {
        $user = auth()->user();

        if (! $this->hasRole($user, UserRole::CommitteeMember)) {
            return $this->errorResponse('This profile route is not available for your role.', [
                'role' => ['Committee member profile completion is only available to committee members.'],
            ], 403);
        }

        $user = $this->profiles->updateCommitteeMemberProfile($user, $data);
        $user = $this->profiles->markProfileCompleted($user);

        return $this->successResponse([
            'user' => new UserResource($user),
        ], 'Profile completed successfully');
    }

    private function hasRole(?User $user, UserRole $role): bool
    {
        return $user?->hasRole($role->value) ?? false;
    }
}
