<?php

namespace App\Repositories;

use App\Interfaces\UserRepositoryInterface;
use App\Models\CommitteeMemberProfile;
use App\Models\StudentProfile;
use App\Models\SupervisorProfile;
use App\Models\User;
use Illuminate\Support\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function createUser(array $data): User
    {
        return User::query()->create($data);
    }

    public function findByEmail(?string $email): ?User
    {
        if (! $email) {
            return null;
        }

        return User::query()->where('email', $email)->first();
    }

    public function findByProvider(string $provider, string $providerId): ?User
    {
        return User::query()
            ->where('provider_name', $provider)
            ->where('provider_id', $providerId)
            ->first();
    }

    public function findByUsernameOrEmail(string $login): ?User
    {
        return User::query()
            ->where('username', $login)
            ->orWhere('email', $login)
            ->first();
    }

    public function findUserById(int $id): ?User
    {
        return User::query()
            ->with(['roles', 'studentProfile', 'supervisorProfile', 'committeeMemberProfile'])
            ->find($id);
    }

    public function findSupervisorById(int $id): ?User
    {
        $user = User::query()->with('roles')->find($id);

        return $user && $user->hasRole('Supervisor') ? $user : null;
    }

    public function findSupervisorByEmail(string $email): ?User
    {
        $user = User::query()->with('roles')->where('email', $email)->first();

        return $user && $user->hasRole('Supervisor') ? $user : null;
    }

    public function findSupervisorByExactName(string $name): ?User
    {
        $matches = User::query()
            ->with('roles')
            ->role('Supervisor')
            ->where('name', $name)
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    public function getPendingApprovalUsers(): Collection
    {
        return User::query()
            ->with(['roles', 'supervisorProfile', 'committeeMemberProfile'])
            ->where('status', 'pending')
            ->role(['Supervisor', 'CommitteeMember'])
            ->get();
    }

    public function linkProviderToUser(User $user, string $provider, string $providerId, ?string $avatar): User
    {
        $user->forceFill([
            'provider_name' => $provider,
            'provider_id' => $providerId,
            'avatar' => $avatar,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $this->loadProfile($user->refresh());
    }

    public function createSocialUser(array $data): User
    {
        return User::query()->create($data);
    }

    public function createStudentProfile(User $user, array $data): StudentProfile
    {
        return $user->studentProfile()->create($data);
    }

    public function createSupervisorProfile(User $user, array $data): SupervisorProfile
    {
        return $user->supervisorProfile()->create($data);
    }

    public function createCommitteeMemberProfile(User $user, array $data): CommitteeMemberProfile
    {
        return $user->committeeMemberProfile()->create($data);
    }

    public function loadProfile(User $user): User
    {
        return $user->loadMissing(['roles', 'studentProfile', 'supervisorProfile', 'committeeMemberProfile']);
    }

    public function updateStatus(User $user, string $status): User
    {
        $user->forceFill([
            'status' => $status,
        ])->save();

        return $this->loadProfile($user->refresh());
    }

    public function markLastLogin(User $user): bool
    {
        return $user->forceFill([
            'last_login_at' => now(),
        ])->save();
    }
}
