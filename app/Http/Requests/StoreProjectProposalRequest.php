<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_team_id' => ['nullable', 'exists:project_teams,id'],

            'title' => ['required', 'string', 'max:255'],

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

            'supervisor' => ['nullable', 'string', 'max:255'],
            'project_teams' => ['nullable', 'string'],

            'status' => ['nullable', 'in:draft,submitted'],
        ];
    }
}