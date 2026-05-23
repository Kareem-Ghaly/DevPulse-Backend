<?php

namespace App\Interfaces;

use App\Models\CommitteeMemberProfile;
use App\Models\StudentProfile;
use App\Models\SupervisorProfile;
use App\Models\User;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    public function createUser(array $data): User;

    public function findByEmail(?string $email): ?User;

    public function findByProvider(string $provider, string $providerId): ?User;

    public function findByUsernameOrEmail(string $login): ?User;

    public function findUserById(int $id): ?User;

    public function getPendingApprovalUsers(): Collection;

    public function linkProviderToUser(User $user, string $provider, string $providerId, ?string $avatar): User;

    public function createSocialUser(array $data): User;

    public function createStudentProfile(User $user, array $data): StudentProfile;

    public function createSupervisorProfile(User $user, array $data): SupervisorProfile;

    public function createCommitteeMemberProfile(User $user, array $data): CommitteeMemberProfile;

    public function loadProfile(User $user): User;

    public function updateStatus(User $user, string $status): User;

    public function markLastLogin(User $user): bool;
}
