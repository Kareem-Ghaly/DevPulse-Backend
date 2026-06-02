<?php

namespace App\Http\Controllers;

use App\Services\ProjectTeamService;

class ProjectTeamController extends Controller
{
    public function __construct(private readonly ProjectTeamService $teams) {}

    public function show(int $projectIdea)
    {
        return $this->teams->show($projectIdea);
    }
}
