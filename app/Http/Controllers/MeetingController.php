<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\ProjectTeam;
use App\Services\MeetingService;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function __construct(private readonly MeetingService $service) {}

    public function index(ProjectTeam $projectTeam)
    {
        return $this->service->index($projectTeam);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_team_id' => ['required', 'exists:project_teams,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15'],
        ]);

        return $this->service->store($validated);
    }

    public function show(Meeting $meeting)
    {
        return $this->service->show($meeting);
    }
}