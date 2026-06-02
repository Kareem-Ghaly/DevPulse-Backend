<?php

namespace App\Repositories;

use App\Interfaces\ProjectIdeaRepositoryInterface;
use App\Models\ProjectIdea;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectIdeaRepository implements ProjectIdeaRepositoryInterface
{
    public function create(array $data): ProjectIdea
    {
        $idea = ProjectIdea::query()->create($data);
        $idea->forceFill([
            'status' => 'published',
            'is_public' => true,
        ])->save();

        return $idea->fresh(['owner']);
    }

    public function update(ProjectIdea $idea, array $data): ProjectIdea
    {
        $idea->update($data);

        return $idea->fresh(['owner']);
    }

    public function delete(ProjectIdea $idea): bool
    {
        return (bool) $idea->delete();
    }

    public function findById(int $id): ?ProjectIdea
    {
        return ProjectIdea::query()
            ->with('owner')
            ->find($id);
    }

    public function findOwnedByUser(int $ideaId, int $ownerId): ?ProjectIdea
    {
        return ProjectIdea::query()
            ->where('id', $ideaId)
            ->where('owner_id', $ownerId)
            ->first();
    }

    public function getPublishedIdeas(array $filters = []): LengthAwarePaginator
    {
        return ProjectIdea::query()
            ->with('owner')
            ->when($filters['owner_id'] ?? null, function ($query, int $ownerId): void {
                $query->where(function ($query) use ($ownerId): void {
                    $query->where('owner_id', $ownerId)
                        ->orWhere(function ($query): void {
                            $query->where('is_public', true)
                                ->whereIn('status', ['published', 'team_completed']);
                        });
                });
            }, function ($query): void {
                $query->where('is_public', true)
                    ->whereIn('status', ['published', 'team_completed']);
            })
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function publish(ProjectIdea $idea): ProjectIdea
    {
        $idea->forceFill([
            'status' => 'published',
            'is_public' => true,
        ])->save();

        return $idea->fresh(['owner']);
    }

    public function markTeamCompleted(ProjectIdea $idea): ProjectIdea
    {
        $idea->forceFill(['status' => 'team_completed'])->save();

        return $idea->fresh(['owner']);
    }

}
