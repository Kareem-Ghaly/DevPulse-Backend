<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'full_name' => ['required', 'string'],
            'university_id' => ['nullable', 'string'],
            'department' => ['nullable', 'string'],
            'academic_year' => ['nullable', 'string'],
            'skills' => ['nullable', 'array'],
            'bio' => ['nullable', 'string'],
        ];
    }
}
