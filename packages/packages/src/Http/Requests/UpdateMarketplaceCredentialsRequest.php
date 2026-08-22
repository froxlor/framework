<?php

namespace Froxlor\Packages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMarketplaceCredentialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['nullable', 'string', 'max:255'],
            'token' => ['required', 'string', 'max:255'],
        ];
    }
}
