<?php
namespace App\Http\Controllers;

use App\Models\FinalSubmission;
use App\Models\ProjectTeam;
use App\Services\FinalSubmissionService;
use Illuminate\Http\Request;

class FinalSubmissionController extends Controller
{
    public function __construct(private readonly FinalSubmissionService $service) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_team_id' => ['required', 'exists:project_teams,id'],
            'proposal_drive_link' => ['nullable', 'url', 'max:500'],
            'presentation_drive_link' => ['nullable', 'url', 'max:500'],
            'code_drive_link' => ['nullable', 'url', 'max:500'],
            'student_notes' => ['nullable', 'string'],
        ]);
        return $this->service->store($validated);
    }

    public function showByTeam(ProjectTeam $projectTeam)
    {
        return $this->service->showByTeam($projectTeam);
    }

    public function index()
    {
        return $this->service->index();
    }

    public function grade(Request $request, FinalSubmission $finalSubmission)
    {
        $validated = $request->validate([
            'proposal_grade' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'proposal_feedback' => ['nullable', 'string'],
            'presentation_grade' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'presentation_feedback' => ['nullable', 'string'],
            'code_grade' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'code_feedback' => ['nullable', 'string'],
        ]);
        return $this->service->grade($finalSubmission, $validated);
    }
}