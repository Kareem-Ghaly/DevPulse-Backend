<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:backlog,todo,in_progress,done'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,ppt,pptx,zip,txt'],
            'link_title' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('status') !== 'done') {
                return;
            }

            if ($this->hasCompletionProof()) {
                return;
            }

            $validator->errors()->add(
                'proof',
                'Completion proof is required. Please upload a file or provide a link.'
            );
        });
    }

    private function hasCompletionProof(): bool
    {
        if ($this->filled('link_url')) {
            return true;
        }

        $files = $this->file('files', []);
        $files = is_array($files) ? $files : [$files];

        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                return true;
            }
        }

        return false;
    }
}
