<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendSupportMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $body = $this->input('body');
        $name = $this->input('guest_name');

        $this->merge([
            'body' => is_string($body) ? trim($body) : $body,
            'guest_name' => is_string($name) ? trim($name) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:'.config('support.max_length')],
            'guest_name' => ['nullable', 'string', 'max:60'],
        ];
    }
}
