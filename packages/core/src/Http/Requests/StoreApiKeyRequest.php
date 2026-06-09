<?php

namespace Froxlor\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'user_id' => ['nullable', 'string', 'exists:users,id'],
            'abilities' => ['nullable', 'string'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
