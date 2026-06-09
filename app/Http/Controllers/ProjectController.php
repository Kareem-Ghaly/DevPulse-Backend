<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProjectFromIdeaRequest;
use App\Services\ProjectService;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $service) {}

    public function createFromIdea(CreateProjectFromIdeaRequest $request, int $projectIdea)
    {
        return $this->service->createFromIdea($projectIdea);
    }

    public function show(int $project)
    {
        return $this->service->show($project);
    }
}
