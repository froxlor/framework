<?php

namespace Froxlor\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
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
            'parent_tenant_id' => 'sometimes|nullable|string|ulid|exists:tenants,id',
            'plan_id' => 'sometimes|string|ulid|exists:plans,id',
            'name' => 'sometimes|string',
            'description' => 'sometimes|nullable|string',
        ];
    }
}
