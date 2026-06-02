<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectIdeaRequest;
use App\Http\Requests\UpdateProjectIdeaRequest;
use App\Services\ProjectIdeaMatchingService;
use App\Services\ProjectIdeaService;

class ProjectIdeaController extends Controller
{
    public function __construct(
        private readonly ProjectIdeaService $projectIdeas,
        private readonly ProjectIdeaMatchingService $matching,
    ) {}

    public function index()
    {
        return $this->projectIdeas->index();
    }

    public function store(StoreProjectIdeaRequest $request)
    {
        return $this->projectIdeas->store($request->validated());
    }

    public function show(int $projectIdea)
    {
        return $this->projectIdeas->show($projectIdea);
    }

    public function update(UpdateProjectIdeaRequest $request, int $projectIdea)
    {
        return $this->projectIdeas->update($projectIdea, $request->validated());
    }

    public function destroy(int $projectIdea)
    {
        return $this->projectIdeas->destroy($projectIdea);
    }

    public function publish(int $projectIdea)
    {
        return $this->projectIdeas->publish($projectIdea);
    }

    public function matchingStudents(int $projectIdea)
    {
        return $this->matching->students($projectIdea);
    }
}
