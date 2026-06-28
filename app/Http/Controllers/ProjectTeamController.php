<?php

namespace App\Http\Controllers;
use App\Services\ProjectTeamService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


class ProjectTeamController extends Controller
{
    public function __construct(private readonly ProjectTeamService $teams) {}

    public function show(int $projectIdea)
    {
        return $this->teams->show($projectIdea);
    }

    public function myProjects(Request $request): JsonResponse
    {
        return $this->teams->getStudentProjects($request->user()->id);
    }

}
