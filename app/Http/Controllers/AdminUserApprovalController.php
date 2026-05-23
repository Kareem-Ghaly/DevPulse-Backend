<?php

namespace App\Http\Controllers;

use App\Services\AdminUserApprovalService;

class AdminUserApprovalController extends Controller
{
    public function __construct(private readonly AdminUserApprovalService $service) {}

    public function pendingUsers()
    {
        return $this->service->pendingUsers();
    }

    public function approveUser(int $user)
    {
        return $this->service->approveUser($user);
    }

    public function rejectUser(int $user)
    {
        return $this->service->rejectUser($user);
    }
}
