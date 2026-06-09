<?php

namespace Froxlor\Database\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'database_name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'engine' => 'nullable|string|max:50',
            'charset' => 'nullable|string|max:50',
            'collation' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50',
        ];
    }
}
