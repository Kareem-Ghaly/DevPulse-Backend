<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteCommitteeMemberProfileRequest;
use App\Http\Requests\CompleteStudentProfileRequest;
use App\Http\Requests\CompleteSupervisorProfileRequest;
use App\Services\ProfileService;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $service) {}

    public function completeStudentProfile(CompleteStudentProfileRequest $request)
    {
        return $this->service->completeStudentProfile($request->validated());
    }

    public function completeSupervisorProfile(CompleteSupervisorProfileRequest $request)
    {
        return $this->service->completeSupervisorProfile($request->validated());
    }

    public function completeCommitteeMemberProfile(CompleteCommitteeMemberProfileRequest $request)
    {
        return $this->service->completeCommitteeMemberProfile($request->validated());
    }
}
