<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PollSupportMessagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'after' => ['nullable', 'integer', 'min:0'],
            'open' => ['nullable', 'boolean'],
        ];
    }
}
