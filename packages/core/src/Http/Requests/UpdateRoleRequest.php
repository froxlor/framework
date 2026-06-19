<?php

namespace Froxlor\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
            'name' => [
                'sometimes',
                'string',
                Rule::unique('roles', 'name')
                    ->where(fn($query) => $this->route('role')?->tenant_id === null
                        ? $query->whereNull('tenant_id')
                        : $query->where('tenant_id', $this->route('role')->tenant_id))
                    ->ignore($this->route('role')),
            ],
            'description' => 'sometimes|nullable|string',
        ];
    }
}
