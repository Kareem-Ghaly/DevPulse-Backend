<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Http\Resources\MeetingResource;
use App\Models\Meeting;
use App\Models\ProjectTeam;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class MeetingService
{
    use ApiResponse;

    public function __construct(private readonly NotificationService $notifications) {}

    public function index(ProjectTeam $projectTeam): JsonResponse
    {
        $meetings = Meeting::where('project_team_id', $projectTeam->id)
            ->with(['team', 'scheduler'])
            ->latest('scheduled_at')
            ->paginate(20);

        return $this->paginatedResponse(
            MeetingResource::collection($meetings),
            $meetings,
            'Meetings retrieved.'
        );
    }

    public function store(array $data): JsonResponse
    {
        $user = auth()->user();
        $team = ProjectTeam::with(['members.user', 'leader', 'proposal.supervisorUser', 'projectIdea'])
            ->findOrFail($data['project_team_id']);

        $notifyUsers = collect();

        if ($user->hasRole('Supervisor')) {
            $proposal = $team->proposal;

            if (! $proposal || (int) $proposal->supervisor_id !== $user->id) {
                return $this->errorResponse('You are not the supervisor of this team.', null, 403);
            }

            $data['scheduler_role'] = 'supervisor';
            $notifyUsers = $team->members->pluck('user')->filter();
            if ($team->leader) {
                $notifyUsers->push($team->leader);
            }
        } elseif ($user->hasRole('Student')) {
            $isMember = (int) $team->leader_id === $user->id
                || $team->members()->where('user_id', $user->id)->exists();

            if (! $isMember) {
                return $this->errorResponse('You are not a member of this team.', null, 403);
            }

            $data['scheduler_role'] = 'student';

            if ($team->proposal?->supervisorUser) {
                $notifyUsers->push($team->proposal->supervisorUser);
            }
        } else {
            return $this->errorResponse('Unauthorized.', null, 403);
        }

        $data['scheduled_by'] = $user->id;
        $data['meeting_link'] = 'https://meet.jit.si/'.Str::slug($team->projectIdea?->title ?? 'meeting').'-'.Str::random(8);

        $meeting = Meeting::create($data);

        $this->notifications->sendToUsers(
            $notifyUsers->filter(fn ($notifyUser): bool => $notifyUser && $notifyUser->id !== $user->id),
            'New Meeting Scheduled',
            "'{$meeting->title}' at ".$meeting->scheduled_at->format('M d, Y H:i'),
            [
                'type' => NotificationType::MeetingScheduled->value,
                'entity_type' => 'meeting',
                'entity_id' => $meeting->id,
                'meeting_id' => $meeting->id,
                'team_id' => $team->id,
                'project_idea_id' => $team->project_idea_id,
                'action_url' => "/meetings?team={$team->id}",
            ]
        );

        return $this->successResponse(
            new MeetingResource($meeting),
            'Meeting scheduled successfully.',
            201
        );
    }

    public function show(Meeting $meeting): JsonResponse
    {
        return $this->successResponse(
            new MeetingResource($meeting->load(['team', 'scheduler'])),
            'Meeting retrieved.'
        );
    }
}
