<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommitteeMemberRegisterRequest extends FormRequest
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
            'academic_title' => ['nullable', 'string'],
            'department' => ['nullable', 'string'],
            'specialization' => ['nullable', 'string'],
            'bio' => ['nullable', 'string'],
        ];
    }
}
