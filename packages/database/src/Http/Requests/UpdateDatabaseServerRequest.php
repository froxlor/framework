<?php

namespace Froxlor\Database\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDatabaseServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'driver' => 'sometimes|nullable|string|max:50',
            'host' => 'sometimes|string|max:255',
            'port' => 'sometimes|integer|min:1|max:65535',
            'admin_username' => 'sometimes|nullable|string|max:255',
            'admin_password' => 'sometimes|nullable|string|max:255',
            'supports_per_environment_users' => 'sometimes|boolean',
            'max_databases' => 'sometimes|nullable|integer|min:1',
        ];
    }
}
