<?php

namespace App\Repositories;

use App\Interfaces\ProfileRepositoryInterface;
use App\Models\User;

class ProfileRepository implements ProfileRepositoryInterface
{
    public function updateStudentProfile(User $user, array $data): User
    {
        $user->studentProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return $this->loadProfile($user);
    }

    public function updateSupervisorProfile(User $user, array $data): User
    {
        $user->supervisorProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return $this->loadProfile($user);
    }

    public function updateCommitteeMemberProfile(User $user, array $data): User
    {
        $user->committeeMemberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return $this->loadProfile($user);
    }

    public function markProfileCompleted(User $user): User
    {
        $user->forceFill([
            'profile_completed' => true,
        ])->save();

        return $this->loadProfile($user->refresh());
    }

    private function loadProfile(User $user): User
    {
        return $user->loadMissing(['roles', 'studentProfile', 'supervisorProfile', 'committeeMemberProfile']);
    }
}
