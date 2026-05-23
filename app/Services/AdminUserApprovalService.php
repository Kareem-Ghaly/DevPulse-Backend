<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Resources\UserResource;
use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminUserApprovalService extends BaseService
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function pendingUsers(): JsonResponse
    {
        return $this->successResponse(
            UserResource::collection($this->users->getPendingApprovalUsers()),
            'Pending users retrieved successfully'
        );
    }

    public function approveUser(int $userId): JsonResponse
    {
        $user = $this->users->findUserById($userId);

        if (! $user) {
            return $this->errorResponse('User not found.', null, 404);
        }

        if (! $this->canBeApprovedOrRejected($user)) {
            return $this->errorResponse('This user type cannot be approved or rejected.', [
                'user' => ['This user type cannot be approved or rejected.'],
            ], 422);
        }

        if ($user->status === UserStatus::ACTIVE->value) {
            return $this->errorResponse('User is already approved.', [
                'status' => ['User is already approved.'],
            ], 422);
        }

        $user = $this->users->updateStatus($user, UserStatus::ACTIVE->value);

        return $this->successResponse(new UserResource($user), 'User approved successfully');
    }

    public function rejectUser(int $userId): JsonResponse
    {
        $user = $this->users->findUserById($userId);

        if (! $user) {
            return $this->errorResponse('User not found.', null, 404);
        }

        if (! $this->canBeApprovedOrRejected($user)) {
            return $this->errorResponse('This user type cannot be approved or rejected.', [
                'user' => ['This user type cannot be approved or rejected.'],
            ], 422);
        }

        if ($user->status === UserStatus::ACTIVE->value) {
            return $this->errorResponse('User is already approved.', [
                'status' => ['User is already approved.'],
            ], 422);
        }

        if ($user->status === UserStatus::REJECTED->value) {
            return $this->errorResponse('User is already rejected.', [
                'status' => ['User is already rejected.'],
            ], 422);
        }

        $user = $this->users->updateStatus($user, UserStatus::REJECTED->value);

        return $this->successResponse(new UserResource($user), 'User rejected successfully');
    }

    private function canBeApprovedOrRejected(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::Supervisor->value,
            UserRole::CommitteeMember->value,
        ]);
    }
}
