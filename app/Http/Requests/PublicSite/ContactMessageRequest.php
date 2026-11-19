<?php

namespace App\Http\Requests\PublicSite;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['name', 'email', 'message'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $normalized[$field] = trim($value);
            }
        }

        if (isset($normalized['email'])) {
            $normalized['email'] = Str::lower($normalized['email']);
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'type' => ['required', 'string', Rule::in(['suggestion', 'question', 'complaint'])],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Enter your name.',
            'name.max' => 'Your name may not exceed 150 characters.',
            'email.required' => 'Enter your email address.',
            'email.email' => 'Enter a valid email address.',
            'email.max' => 'Your email address may not exceed 255 characters.',
            'type.required' => 'Choose a topic.',
            'type.in' => 'Choose a valid contact topic.',
            'message.required' => 'Enter a message.',
            'message.max' => 'Your message may not exceed 5000 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'name',
            'email' => 'email address',
            'type' => 'topic',
            'message' => 'message',
        ];
    }
}
