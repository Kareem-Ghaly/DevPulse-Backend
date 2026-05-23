<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupervisorRegisterRequest extends FormRequest
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
            'academic_title' => ['required', 'string'],
            'department' => ['required', 'string'],
            'specialization' => ['required', 'string'],
            'office_hours' => ['nullable', 'string'],
            'bio' => ['nullable', 'string'],
        ];
    }
}
