<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteSupervisorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'academic_title' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'specialization' => ['required', 'string', 'max:255'],
            'office_hours' => ['nullable', 'string'],
            'bio' => ['nullable', 'string'],
            'research_interests' => ['required', 'array'], 
            'research_interests.*' => ['string'],
        ];
    }
}
