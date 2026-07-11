<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskAttachmentsRequest;
use App\Http\Requests\StoreTaskLinkRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\ProjectTeam;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskLink;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $tasks) {}

    public function members(ProjectTeam $projectTeam): JsonResponse
    {
        return $this->tasks->members($projectTeam);
    }

    public function index(ProjectTeam $projectTeam): JsonResponse
    {
        return $this->tasks->index($projectTeam);
    }

    public function store(StoreTaskRequest $request, ProjectTeam $projectTeam): JsonResponse
    {
        return $this->tasks->store($projectTeam, $request->validated());
    }

    public function show(Task $task): JsonResponse
    {
        return $this->tasks->show($task);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        return $this->tasks->update($task, $request->validated());
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        return $this->tasks->updateStatus($task, $request->validated());
    }

    public function destroy(Task $task): JsonResponse
    {
        return $this->tasks->destroy($task);
    }

    public function addAttachments(StoreTaskAttachmentsRequest $request, Task $task): JsonResponse
    {
        return $this->tasks->addAttachments($task, $request->validated());
    }

    public function deleteAttachment(TaskAttachment $attachment): JsonResponse
    {
        return $this->tasks->deleteAttachment($attachment);
    }

    public function addLink(StoreTaskLinkRequest $request, Task $task): JsonResponse
    {
        return $this->tasks->addLink($task, $request->validated());
    }

    public function deleteLink(TaskLink $link): JsonResponse
    {
        return $this->tasks->deleteLink($link);
    }
}
