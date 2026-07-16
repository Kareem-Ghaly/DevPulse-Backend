<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFirebaseTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'device_type' => ['nullable', 'string', 'max:255'],
            'browser' => ['nullable', 'string', 'max:255'],
        ];
    }
}