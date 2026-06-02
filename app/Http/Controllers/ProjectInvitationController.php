<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendProjectInvitationRequest;
use App\Services\ProjectInvitationService;

class ProjectInvitationController extends Controller
{
    public function __construct(private readonly ProjectInvitationService $invitations) {}

    public function send(SendProjectInvitationRequest $request, int $projectIdea)
    {
        return $this->invitations->send($projectIdea, $request->validated());
    }

    public function myInvitations()
    {
        return $this->invitations->myInvitations();
    }

    public function accept(int $invitation)
    {
        return $this->invitations->accept($invitation);
    }

    public function reject(int $invitation)
    {
        return $this->invitations->reject($invitation);
    }
}
