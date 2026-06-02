<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'abstract' => ['sometimes', 'required', 'string'],
            'description' => ['sometimes', 'required', 'string'],
            'team_size' => ['sometimes', 'required', 'integer', 'min:1'],
            'required_skills' => ['sometimes', 'required', 'array', 'min:1'],
            'required_skills.*' => ['string', 'max:100'],
        ];
    }
}
