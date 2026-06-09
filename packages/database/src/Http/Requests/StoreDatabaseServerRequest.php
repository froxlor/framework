<?php

namespace Froxlor\Database\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDatabaseServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'driver' => 'nullable|string|max:50',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'admin_username' => 'nullable|string|max:255',
            'admin_password' => 'nullable|string|max:255',
            'supports_per_environment_users' => 'nullable|boolean',
            'max_databases' => 'nullable|integer|min:1',
        ];
    }
}
