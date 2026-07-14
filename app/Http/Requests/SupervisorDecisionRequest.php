<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupervisorDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:approved,rejected,changes_requested,needs_revision,supervisor_approved,supervisor_rejected'],
            'notes' => ['nullable', 'string'],
        ];
    }
}