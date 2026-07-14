<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitProjectProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supervisor_id' => ['nullable', 'required_without:supervisor', 'exists:users,id'],
            'supervisor' => ['nullable', 'required_without:supervisor_id', 'string', 'max:255'],
        ];
    }
}