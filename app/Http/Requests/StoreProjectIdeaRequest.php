<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['required', 'string'],
            'description' => ['required', 'string'],
            'team_size' => ['required', 'integer', 'min:1'],
            'required_skills' => ['required', 'array', 'min:1'],
            'required_skills.*' => ['string', 'max:100'],
        ];
    }
}
