<?php

namespace Froxlor\Core\Http\Requests;

use Froxlor\Core\Rules\SshPrivateKey;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNodeRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'hostname' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
            'ssh_key' => ['nullable', 'string', new SshPrivateKey($this->input('password'))],
            'sudo' => 'boolean',
            'description' => 'nullable|string',
        ];
    }
}
