<?php

namespace Froxlor\Packages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRepositoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|alpha_num',
            'type' => 'required|string',
            'url' => 'required|string',
            'options' => 'nullable|array',
            'auth' => 'nullable|array',
            'auth.type' => 'required_with:auth|string|in:http-basic,bearer',
            'auth.username' => 'required_if:auth.type,http-basic|string',
            'auth.password' => 'required_if:auth.type,http-basic|string',
            'auth.token' => 'required_if:auth.type,bearer|string',
        ];
    }
}
