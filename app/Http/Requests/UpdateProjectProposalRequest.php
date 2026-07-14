<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_team_id' => ['nullable', 'exists:project_teams,id'],

            'title' => ['sometimes', 'string', 'max:255'],

            'problem' => ['nullable', 'string'],
            'problem_overview' => ['nullable', 'string'],
            'comparison_table_with_similar_applications' => ['nullable', 'string'],
            'project_users' => ['nullable', 'string'],

            'mind_map_problem' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            'solution_overview' => ['nullable', 'string'],
            'proposed_solution' => ['nullable', 'string'],

            'mind_map_solution' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],

            'functional_requirements' => ['nullable', 'string'],
            'non_functional_requirements' => ['nullable', 'string'],
            'project_management' => ['nullable', 'string'],
            'programming_languages' => ['nullable', 'string'],

            'supervisor_id' => ['nullable', 'exists:users,id'],
            'supervisor' => ['nullable', 'string', 'max:255'],
            'project_teams' => ['nullable', 'string'],

            'status' => ['nullable', 'in:draft,submitted,under_review,needs_changes,approved,rejected,changes_requested,needs_revision,supervisor_approved,supervisor_rejected,submitted_to_committee,committee_approved,committee_rejected,committee_needs_revision'],
        ];
    }
}