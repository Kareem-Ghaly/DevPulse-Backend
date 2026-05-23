<?php

namespace App\Interfaces;

use App\Models\User;

interface ProfileRepositoryInterface
{
    public function updateStudentProfile(User $user, array $data): User;

    public function updateSupervisorProfile(User $user, array $data): User;

    public function updateCommitteeMemberProfile(User $user, array $data): User;

    public function markProfileCompleted(User $user): User;
}
