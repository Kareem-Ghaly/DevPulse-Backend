<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProjectProposalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'project_team_id' => $this->project_team_id,
            'created_by' => $this->created_by,
            'last_updated_by' => $this->last_updated_by,

            'title' => $this->title,

            'problem' => $this->problem,
            'problem_overview' => $this->problem_overview,
            'comparison_table_with_similar_applications' => $this->comparison_table_with_similar_applications,
            'project_users' => $this->project_users,

            'mind_map_problem' => $this->mind_map_problem,
            'mind_map_problem_url' => $this->mind_map_problem
                ? Storage::disk('public')->url($this->mind_map_problem)
                : null,

            'solution_overview' => $this->solution_overview,
            'proposed_solution' => $this->proposed_solution,

            'mind_map_solution' => $this->mind_map_solution,
            'mind_map_solution_url' => $this->mind_map_solution
                ? Storage::disk('public')->url($this->mind_map_solution)
                : null,

            'functional_requirements' => $this->functional_requirements,
            'non_functional_requirements' => $this->non_functional_requirements,
            'project_management' => $this->project_management,
            'programming_languages' => $this->programming_languages,

            'supervisor' => $this->supervisor,
            'project_teams' => $this->project_teams,

            'status' => $this->status,
            'supervisor_notes' => $this->supervisor_notes,
            'supervisor_decided_at' => $this->supervisor_decided_at,
            'last_update' => $this->last_update,

            'team' => $this->whenLoaded('team'),
            'last_updater' => $this->whenLoaded('lastUpdater'),
            'committee_reviews' => $this->whenLoaded('committeeReviews'),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}