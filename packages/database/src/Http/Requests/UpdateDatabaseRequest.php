<?php

namespace Froxlor\Database\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'database_name' => 'sometimes|nullable|string|max:255',
            'username' => 'sometimes|nullable|string|max:255',
            'password' => 'sometimes|nullable|string|max:255',
            'engine' => 'sometimes|nullable|string|max:50',
            'charset' => 'sometimes|nullable|string|max:50',
            'collation' => 'sometimes|nullable|string|max:100',
            'status' => 'sometimes|nullable|string|max:50',
            'last_error' => 'sometimes|nullable|string',
        ];
    }
}
