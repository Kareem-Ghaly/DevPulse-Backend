<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = $this->getRoleNames()->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $role,
            'status' => $this->status,
            'profile_completed' => $this->profile_completed,
            'profile' => $this->profileForRole($role),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function profileForRole(?string $role): ?array
    {
        return match ($role) {
            'Student' => $this->studentProfile ? [
                'full_name' => $this->studentProfile->full_name,
                'university_id' => $this->studentProfile->university_id,
                'department' => $this->studentProfile->department,
                'academic_year' => $this->studentProfile->academic_year,
                'skills' => $this->studentProfile->skills,
                'bio' => $this->studentProfile->bio,
            ] : null,
            'Supervisor' => $this->supervisorProfile ? [
                'full_name' => $this->supervisorProfile->full_name,
                'academic_title' => $this->supervisorProfile->academic_title,
                'department' => $this->supervisorProfile->department,
                'specialization' => $this->supervisorProfile->specialization,
                'office_hours' => $this->supervisorProfile->office_hours,
                'bio' => $this->supervisorProfile->bio,
            ] : null,
            'CommitteeMember' => $this->committeeMemberProfile ? [
                'full_name' => $this->committeeMemberProfile->full_name,
                'academic_title' => $this->committeeMemberProfile->academic_title,
                'department' => $this->committeeMemberProfile->department,
                'specialization' => $this->committeeMemberProfile->specialization,
                'bio' => $this->committeeMemberProfile->bio,
            ] : null,
            default => null,
        };
    }
}
